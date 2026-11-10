<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function is_int;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * @internal
 */
final class ClassificationStoreResolver implements
    ColumnResolverInterface,
    CoreElementColumnResolverInterface,
    ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    public function __construct(
        private readonly GridServiceInterface $gridService,
        private readonly KeyRelationResolverInterface $keyRelationResolver,
        private readonly ServiceResolverInterface $classificationStoreServiceResolver,
        private readonly DataServiceInterface $dataService,
        private readonly KeyQualifierResolverInterface $keyQualifierResolver,
        private readonly QualifiedValueSerializerInterface $qualifiedValueSerializer,
        private readonly ToolResolverInterface $toolResolver,
        private readonly LocalizedFieldResolverInterface $localizedFieldResolver,
        private readonly ColumnReferenceServiceInterface $columnReferenceService,
    ) {
    }

    /** @see LocalizedValueTrait::doGetFallbackValues() */
    protected function doGetFallbackValues(): bool
    {
        return $this->localizedFieldResolver->doGetFallbackValues();
    }

    /** @see LocalizedValueTrait::getDefaultLanguage() */
    protected function getDefaultLanguage(): ?string
    {
        return $this->toolResolver->getDefaultLanguage();
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        if (!$this->hasKeyConfig($column->getConfig())) {
            return $this->getEmptyColumnData($column);
        }

        $config = $this->getReference($column, $element);
        $baseResolver = $this->gridService->getColumnResolvers()['dataobject.adapter'];

        if (!$baseResolver instanceof CoreElementColumnResolverInterface) {
            throw new InvalidArgumentException(
                'Can not load adapter resolver. This is needed to get base data of the Classification Store'
            );
        }

        $normalizedData = $baseResolver->resolveForCoreElement($column, $element);

        $locale = 'default';
        if ($column->getLocale()) {
            $locale = $column->getLocale();
        }

        try {
            $value = $normalizedData->getValue()[$config->getGroupId()][$locale][$config->getKeyId()];
        } catch (Exception $e) {
            $value = null;
        }

        // one cell holds one value, so a `multiValue` key shows its default slot - the same reduction
        // the export makes ({@see self::resolveForExport()})
        $keyConfig = KeyConfig::getById($config->getKeyId());
        if ($keyConfig instanceof KeyConfig && $this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            $value = $this->qualifiedValueSerializer->getDefaultSlotValue($value);
        }

        $keyGroupRelation = $this->findKeyGroupRelation($config->getGroupId(), $config->getKeyId());

        try {
            $inheritance = $normalizedData->getInheritance()[$config->getGroupId()][$locale][$config->getKeyId()];
        } catch (Exception) {
            $inheritance = new InheritanceData($element->getId(), false);
        }

        $returnData = new ColumnData(
            key: $column->getKey() . '.' . $keyGroupRelation->getName(),
            locale: $column->getLocale(),
            value: $value,
            fieldType: $keyGroupRelation->getType(),
            inheritance: $inheritance,
        );

        $returnData->addAdditionalAttribute('groupId', $config->getGroupId());
        $returnData->addAdditionalAttribute('keyId', $config->getKeyId());

        return $returnData;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
    {
        if (!$this->hasKeyConfig($column->getConfig())) {
            return $this->getEmptyColumnData($column);
        }

        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $config = $this->getReference($column, $element);
        $keyConfig = KeyConfig::getById(
            $config->getKeyId(),
        );

        if (!$keyConfig instanceof KeyConfig) {
            throw new InvalidArgumentException(sprintf('Key with %s not set', $config->getKeyId()));
        }

        $fieldDefinition = $this->classificationStoreServiceResolver->getFieldDefinitionFromJson(
            json_decode($keyConfig->getDefinition(), true),
            $keyConfig->getType()
        );

        $locale = 'default';
        if ($column->getLocale()) {
            $locale = $column->getLocale();
        }

        $legacyParameters = ['context' => [
            'containerType' => 'classificationstore',
            'fieldname' => $column->getKey(),
            'groupId' => $config->getGroupId(),
            'keyId' => $config->getKeyId(),
            'language' => $locale,
        ]];

        // the cell shows the default slot of a `multiValue` key, so the export injects that same one value
        $defaultValue = $this->keyQualifierResolver->declaresQualifiers($keyConfig)
            ? $this->getDefaultSlotValue($element, $column->getKey(), $config, $locale, $keyConfig)
            : null;

        if ($defaultValue !== null) {
            $legacyParameters['injectedData'] = $defaultValue;
        }

        $value = $this->dataService->getExportFieldValue(
            $element,
            $fieldDefinition,
            $column->getKey(),
            new FieldContextData(legacyParameters: $legacyParameters)
        );

        return $this->getColumnData($column, $value, $fieldDefinition->getFieldType());
    }

    public function findKeyGroupRelation(int $groupId, int $keyId): KeyGroupRelation
    {
        $keys = $this->keyRelationResolver->getByGroup($groupId);

        foreach ($keys as $key) {
            if ($key->getKeyId() === $keyId) {
                return $key;
            }
        }

        throw new InvalidArgumentException(sprintf('Key with %s not set', $keyId));
    }

    public function getType(): string
    {
        return 'dataobject.classificationstore';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_OBJECT,
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validateConfig(array $config): ClassificationStoreConfig
    {
        if (!array_key_exists('groupId', $config) || !array_key_exists('keyId', $config)) {
            throw new InvalidArgumentException(
                'dataobject.classificationstore needs groupId and keyId, '
                . 'or groupIdentifier and keyIdentifier, as config'
            );
        }

        if (!is_int($config['groupId']) || !is_int($config['keyId'])) {
            throw new InvalidArgumentException('groupId and keyId must be type of integer');
        }

        return new ClassificationStoreConfig($config['groupId'], $config['keyId']);
    }

    /**
     * The identifiers, when the config carries both, are the reference and the integer pair is not consulted.
     *
     * @throws InvalidArgumentException
     */
    private function getReference(Column $column, ElementInterface $element): ClassificationStoreConfig
    {
        $reference = null;
        $classId = $element instanceof Concrete ? $element->getClassId() : null;
        if ($classId !== null) {
            $reference = $this->columnReferenceService->resolve(
                $classId,
                $column->getKey(),
                $column->getConfig()
            );
        }

        return $reference ?? $this->validateConfig($column->getConfig());
    }

    /**
     * The one value a `multiValue` key shows at a (group, key, language): the values the store holds,
     * reduced the way the grid cell reduces the payload the object data endpoint publishes.
     *
     * The export is formatted by the key's own field definition, which reads the slot itself and
     * cannot be handed a value - except through `injectedData`, which
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data::getDataFromObjectParam()} honours ahead
     * of its own read.
     *
     * @throws Exception
     */
    private function getDefaultSlotValue(
        Concrete $element,
        string $fieldName,
        ClassificationStoreConfig $config,
        string $locale,
        KeyConfig $keyConfig
    ): mixed {
        // the guard core's own read of this slot makes, so that a saved column naming a field the
        // class no longer has keeps exporting an empty cell rather than failing the export
        if (!method_exists($element, 'get' . ucfirst($fieldName))) {
            return null;
        }

        $container = $element->get($fieldName);

        if (!$container instanceof Classificationstore) {
            return null;
        }

        return $this->qualifiedValueSerializer->getDefaultSlotValue(
            $this->qualifiedValueSerializer->toPayload(
                $this->qualifiedValueSerializer->readStoredEntries(
                    $container,
                    $config->getGroupId(),
                    $config->getKeyId(),
                    $locale,
                    $keyConfig
                )
            )
        );
    }

    /**
     * A bare classification store field column (none of groupId, keyId, groupIdentifier, keyIdentifier) cannot be
     * resolved to a value. A column with only part of a reference is malformed and still goes to
     * validateConfig(), which throws.
     */
    private function hasKeyConfig(array $config): bool
    {
        return array_key_exists('groupId', $config)
            || array_key_exists('keyId', $config)
            || ($config['groupIdentifier'] ?? null) !== null
            || ($config['keyIdentifier'] ?? null) !== null;
    }

    private function getEmptyColumnData(Column $column): ColumnData
    {
        return $this->getColumnData($column, null, $column->getType());
    }
}
