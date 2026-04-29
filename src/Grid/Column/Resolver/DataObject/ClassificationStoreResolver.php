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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
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
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function is_int;
use function sprintf;

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
        private readonly KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private readonly ServiceResolverInterface $classificationStoreServiceResolver,
        private readonly DataServiceInterface $dataService,
        private readonly ToolResolverInterface $toolResolver,
        private readonly LocalizedFieldResolverInterface $localizedFieldResolver,
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
        $config = $this->validateConfig($column->getConfig());
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

    public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $config = $this->validateConfig($column->getConfig());
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

        $context = new FieldContextData(
            legacyParameters: ['context' => [
                'containerType' => 'classificationstore',
                'fieldname' => $column->getKey(),
                'groupId' => $config->getGroupId(),
                'keyId' => $config->getKeyId(),
                'language' => $locale,
            ]]
        );

        $value = $this->dataService->getExportFieldValue(
            $element,
            $fieldDefinition,
            $column->getKey(),
            $context
        );

        return $this->getColumnData($column, $value, $fieldDefinition->getFieldType());
    }

    public function findKeyGroupRelation(int $groupId, int $keyId): KeyGroupRelation
    {
        $keys = $this->keyGroupRelationRepository->getByGroupId($groupId);

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
            throw new InvalidArgumentException('dataobject.classificationstore needs groupId and keyId as config');
        }

        if (!is_int($config['groupId']) || !is_int($config['keyId'])) {
            throw new InvalidArgumentException('groupId and keyId must be type of integer');
        }

        return new ClassificationStoreConfig($config['groupId'], $config['keyId']);
    }
}
