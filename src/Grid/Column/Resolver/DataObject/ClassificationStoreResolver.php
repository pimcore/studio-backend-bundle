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

        // one cell holds one value, so a key declaring qualifiers shows its default slot; a key
        // declaring nothing was published as one value already, whatever its row still holds.
        //
        // The list the adapter published is the list the row holds, so the one reader answers for
        // both and the cell cannot drift from the export ({@see self::resolveForExport()}). The
        // empty list a key publishes whose declaration hides every entry it holds reduces to null
        // here - an empty cell - which is the same nothing the export selects out of that row
        $keyConfig = KeyConfig::getById($config->getKeyId());
        if ($keyConfig instanceof KeyConfig && $this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            $value = $this->qualifiedValueSerializer->getDefaultSlotValue($value, $keyConfig);
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

        // the cell shows the visible default slot, so the export injects that same one value
        $storedSlot = $this->getStoredSlot($element, $column->getKey(), $config, $locale);

        if ($this->qualifiedValueSerializer->holdsEntries($storedSlot, $keyConfig)) {
            $legacyParameters['injectedData'] = $this->qualifiedValueSerializer
                ->getDefaultSlotValue($storedSlot, $keyConfig);
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
            throw new InvalidArgumentException('dataobject.classificationstore needs groupId and keyId, or groupIdentifier and keyIdentifier, as config');
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
     * The value the row holds at one slot, read the way the export's own read reads it.
     *
     * The export is formatted by the key's own field definition, which reads the slot itself and
     * cannot be handed a value - except through `injectedData`, which
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data::getDataFromObjectParam()} honours ahead
     * of its own read and {@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\EncryptedFieldAdapter}
     * already uses. So the slot is read here, reduced, and injected, rather than the definition
     * being handed the list {@see self::resolveForCoreElement()} reduces away for the cell.
     *
     * @throws Exception
     */
    private function getStoredSlot(
        Concrete $element,
        string $fieldName,
        ClassificationStoreConfig $config,
        string $locale
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

        return $container->getLocalizedKeyValue(
            $config->getGroupId(),
            $config->getKeyId(),
            $locale,
            true,
            true
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
