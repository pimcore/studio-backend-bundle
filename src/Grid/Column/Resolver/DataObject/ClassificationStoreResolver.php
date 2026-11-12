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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function array_map;
use function is_int;
use function is_string;
use function json_encode;
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
        private readonly KeyConfigResolverInterface $keyConfigResolver,
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

        // one cell holds one variant of a `multiValue` key, the one its column addresses
        $keyConfig = $this->keyConfigResolver->getById($config->getKeyId());
        if ($keyConfig instanceof KeyConfig && $this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            $value = $this->getCellValue($this->qualifiedValueSerializer->getVariantValues(
                $value,
                $config->getVariant()
            ), $keyConfig, $config->getVariant());
        }

        $keyGroupRelation = $this->findKeyGroupRelation($config->getGroupId(), $config->getKeyId());

        try {
            $inheritance = $normalizedData->getInheritance()[$config->getGroupId()][$locale][$config->getKeyId()];
        } catch (Exception) {
            $inheritance = new InheritanceData($element->getId(), false);
        }

        $returnData = new ColumnData(
            key: $column->getKey() . '.' . $keyGroupRelation->getName() . $this->getVariantSuffix($config),
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
        $keyConfig = $this->keyConfigResolver->getById(
            $config->getKeyId(),
        );

        if (!$keyConfig instanceof KeyConfig) {
            throw new InvalidArgumentException(sprintf('Key with %s not set', $config->getKeyId()));
        }

        // the assignment's effective definition, so that its units and options format the value
        $relation = $this->findKeyGroupRelation($config->getGroupId(), $config->getKeyId());
        $fieldDefinition = $this->classificationStoreServiceResolver->getFieldDefinitionFromJson(
            $relation->getEffectiveDefinitionData(),
            $relation->getType()
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

        if ($this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            $value = $this->getQualifiedExportValue(
                $element,
                $column->getKey(),
                $config,
                $locale,
                $keyConfig,
                $fieldDefinition,
                $legacyParameters
            );

            return $this->getColumnData($column, $value, $fieldDefinition->getFieldType());
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

        $variant = $config['variant'] ?? null;
        if ($variant !== null && (!is_string($variant) || $variant === '')) {
            throw new InvalidArgumentException('variant must be a non-empty string');
        }

        return new ClassificationStoreConfig($config['groupId'], $config['keyId'], $variant);
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
     * The cell of a `multiValue` column: the one value of its variant, or for a repeating key the entry list
     * the write path reads ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser}),
     * so that a cell sent back unchanged keeps every position. Joining the list for display is the renderer's.
     *
     * @param mixed[] $values
     *
     * @return mixed|array<int, array<string, mixed>>
     */
    private function getCellValue(array $values, KeyConfig $keyConfig, ?string $variant): mixed
    {
        if ($values === []) {
            return null;
        }

        if ($this->keyQualifierResolver->isMultiValue($keyConfig) !== true) {
            return $values[0];
        }

        $entries = [];
        foreach (array_values($values) as $position => $value) {
            $entry = [QualifiedValueSerializerInterface::VALUE_MEMBER => $value];
            if ($variant !== null) {
                $entry[QualifiedValueSerializerInterface::VARIANT_MEMBER] = $variant;
            }
            $entry[QualifiedValueSerializerInterface::POSITION_MEMBER] = $position;
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * The export of a `multiValue` column: the values its variant holds, each formatted by the key's own
     * field definition, which reads the slot itself and cannot be handed a value except through
     * `injectedData` ({@see \Pimcore\Model\DataObject\ClassDefinition\Data::getDataFromObjectParam()}).
     * A repeating key exports them as a bare JSON array, one entry per position.
     *
     * @param array<string, mixed> $legacyParameters
     *
     * @throws Exception
     */
    private function getQualifiedExportValue(
        Concrete $element,
        string $fieldName,
        ClassificationStoreConfig $config,
        string $locale,
        KeyConfig $keyConfig,
        Data $fieldDefinition,
        array $legacyParameters
    ): mixed {
        // the guard core's own read of this slot makes, so that a saved column naming a field the
        // class no longer has keeps exporting an empty cell rather than failing the export
        if (!method_exists($element, 'get' . ucfirst($fieldName))) {
            return '';
        }

        $container = $element->get($fieldName);

        if (!$container instanceof Classificationstore) {
            return '';
        }

        $values = $this->qualifiedValueSerializer->getVariantValues(
            $this->qualifiedValueSerializer->toPayload(
                $this->qualifiedValueSerializer->readStoredEntries(
                    $container,
                    $config->getGroupId(),
                    $config->getKeyId(),
                    $locale,
                    $keyConfig
                )
            ),
            $config->getVariant()
        );

        // not reading the slot itself, which would answer the default slot for a variant that holds nothing
        $formatted = array_map(
            fn (mixed $value): mixed => $this->dataService->getExportFieldValue(
                $element,
                $fieldDefinition,
                $fieldName,
                new FieldContextData(legacyParameters: [...$legacyParameters, 'injectedData' => $value])
            ),
            $values
        );

        if ($formatted === []) {
            return '';
        }

        return $this->keyQualifierResolver->isMultiValue($keyConfig) === true
            ? json_encode($formatted)
            : $formatted[0];
    }

    private function getVariantSuffix(ClassificationStoreConfig $config): string
    {
        return $config->getVariant() !== null ? '#' . $config->getVariant() : '';
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
