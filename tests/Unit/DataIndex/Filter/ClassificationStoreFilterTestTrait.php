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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * Applies a classification store filter against a recording query. Needs ColumnFilterMockTrait.
 *
 * @internal
 */
trait ClassificationStoreFilterTestTrait
{
    private const KEY_NAME = 'weight';

    private const GROUP_NAME = 'dimensions';

    private const FIELD_NAME = 'classificationStoreField';

    /**
     * @param class-string<FilterInterface> $filterClass
     *
     * @return array<int, array{fieldName: string, group: string, filter: mixed}> the recorded filter calls
     */
    private function applyFilter(string $filterClass, string $columnType, array $value): array
    {
        $calls = [];
        $query = null;
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'classificationStoreFilter' => function (
                string $fieldName,
                string $group,
                mixed $filter
            ) use (&$calls, &$query): mixed {
                $calls[] = ['fieldName' => $fieldName, 'group' => $group, 'filter' => $filter];

                return $query;
            },
        ]);

        $key = new KeyGroupRelation();
        $key->setName(self::KEY_NAME);
        $group = new GroupConfig();
        $group->setName(self::GROUP_NAME);

        $filter = new $filterClass(
            $this->makeEmpty(GroupConfigRepositoryInterface::class, ['getById' => $group]),
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class, ['getByKeyGroupId' => $key])
        );

        $parameter = $this->getColumnFilterMock(
            self::FIELD_NAME,
            $columnType,
            ['groupId' => 1, 'keyId' => 2, 'value' => $value]
        );

        $filter->apply($parameter, $query);

        return $calls;
    }
}
