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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\Element\ElementInterface;
use function array_key_exists;
use function is_int;
use function sprintf;

/**
 * @internal
 */
final class ClassificationStoreResolver implements ColumnResolverInterface, CoreElementColumnResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    public function __construct(
        private readonly GridServiceInterface $gridService,
        private readonly KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,

    ) {
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
                "Can not load adapter resolver. This is neded to get base data of the Classification Store"
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

        return new ColumnData(
            key: $column->getKey() . '.' . $keyGroupRelation->getName(),
            locale: $column->getLocale(),
            value: $value,
            fieldType: $keyGroupRelation->getType(),
            inheritance: $normalizedData->getInheritance(),
        );
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
