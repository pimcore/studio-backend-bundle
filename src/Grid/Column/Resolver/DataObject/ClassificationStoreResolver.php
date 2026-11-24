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
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\DeniedColumnDataResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\QualifiedColumnValueResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class ClassificationStoreResolver implements
    ColumnResolverInterface,
    CoreElementColumnResolverInterface,
    DeniedColumnDataResolverInterface,
    ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    public function __construct(
        private readonly GridServiceInterface $gridService,
        private readonly KeyRelationResolverInterface $keyRelationResolver,
        private readonly ServiceResolverInterface $classificationStoreServiceResolver,
        private readonly DataServiceInterface $dataService,
        private readonly ToolResolverInterface $toolResolver,
        private readonly LocalizedFieldResolverInterface $localizedFieldResolver,
        private readonly ColumnReferenceServiceInterface $columnReferenceService,
        private readonly QualifiedColumnValueResolverInterface $qualifiedColumnValueResolver,
        private readonly KeyLocalizationResolverInterface $keyLocalizationResolver,
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

    public function resolveDeniedForCoreElement(Column $column, ElementInterface $element): ?ColumnData
    {
        if (!$this->hasKeyConfig($column->getConfig())) {
            return null;
        }

        try {
            $config = $this->getReference($column, $element);
            $keyGroupRelation = $this->findKeyGroupRelation($config->getGroupId(), $config->getKeyId());
        } catch (Exception) {
            return null;
        }

        $denied = new ColumnData(
            key: $column->getKey() . '.' . $keyGroupRelation->getName() . $this->getVariantSuffix($config),
            locale: $column->getLocale(),
            value: null,
            fieldType: $keyGroupRelation->getType(),
        );

        $denied->addAdditionalAttribute('groupId', $config->getGroupId());
        $denied->addAdditionalAttribute('keyId', $config->getKeyId());

        return $denied;
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

        $locale = $this->resolveLanguage($column, $element, $config);

        try {
            $value = $normalizedData->getValue()[$config->getGroupId()][$locale][$config->getKeyId()];
        } catch (Exception $e) {
            $value = null;
        }

        // one cell holds one variant of a `multiValue` key, the one its column addresses
        $value = $this->qualifiedColumnValueResolver->getCellValue($value, $config);

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
        $declaresQualifiers = $this->qualifiedColumnValueResolver->declaresQualifiers($config);

        // the assignment's effective definition, so that its units and options format the value
        $relation = $this->findKeyGroupRelation($config->getGroupId(), $config->getKeyId());
        $fieldDefinition = $this->classificationStoreServiceResolver->getFieldDefinitionFromJson(
            $relation->getEffectiveDefinitionData(),
            $relation->getType()
        );

        $locale = $this->resolveLanguage($column, $element, $config);

        $legacyParameters = ['context' => [
            'containerType' => 'classificationstore',
            'fieldname' => $column->getKey(),
            'groupId' => $config->getGroupId(),
            'keyId' => $config->getKeyId(),
            'language' => $locale,
        ]];

        if ($declaresQualifiers) {
            $value = $this->qualifiedColumnValueResolver->getExportValue(
                $element,
                $column->getKey(),
                $config,
                $locale,
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

    /**
     * The bucket language the key decides, the rule the filter path takes: a key that is not
     * localized is read from the language independent bucket whatever the column carries, a
     * localized one from the column's locale or, without one, the implicit read locale.
     */
    private function resolveLanguage(
        Column $column,
        ElementInterface $element,
        ClassificationStoreConfig $config,
    ): string {
        $definition = $element instanceof Concrete
            ? $element->getClass()->getFieldDefinition($column->getKey())
            : null;
        $columnLocale = $column->getLocale();

        if (!$definition instanceof Classificationstore) {
            return $columnLocale !== null && $columnLocale !== ''
                ? $columnLocale
                : MappingProperty::NOT_LOCALIZED_KEY;
        }

        if (!$this->keyLocalizationResolver->isKeyLocalized($config->getKeyId(), $definition)) {
            return MappingProperty::NOT_LOCALIZED_KEY;
        }

        if ($columnLocale !== null && $columnLocale !== '') {
            return $columnLocale;
        }

        return $this->gridService->getImplicitReadLocale() ?? MappingProperty::NOT_LOCALIZED_KEY;
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
