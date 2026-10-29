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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Query;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\MultiSelectFilter;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQuery;

/**
 * @internal
 */
final class DataObjectQueryTest extends Unit
{
    public function testMultiSelectDoesNotMatchMissingValuesByDefault(): void
    {
        $filter = $this->getMultiSelectFilter(fn (DataObjectQuery $query) => $query->filterMultiSelect('color', ['']));

        $this->assertSame('color', $filter->getField());
        $this->assertSame([''], $filter->getValues());
        $this->assertFalse($filter->isMatchMissingForEmptyValueEnabled());
    }

    public function testMultiSelectPassesMatchMissingForEmptyValue(): void
    {
        $filter = $this->getMultiSelectFilter(
            fn (DataObjectQuery $query) => $query->filterMultiSelect('color', [''], false, true)
        );

        $this->assertFalse($filter->isPqlFieldNameResolutionEnabled());
        $this->assertTrue($filter->isMatchMissingForEmptyValueEnabled());
    }

    private function getMultiSelectFilter(callable $apply): MultiSelectFilter
    {
        $search = new DataObjectSearch();
        $apply(new DataObjectQuery($search, $this->makeEmpty(ClassDefinitionResolverInterface::class)));

        $modifiers = $search->getModifiers();
        $this->assertCount(1, $modifiers);
        $this->assertInstanceOf(MultiSelectFilter::class, $modifiers[0]);

        return $modifiers[0];
    }
}
