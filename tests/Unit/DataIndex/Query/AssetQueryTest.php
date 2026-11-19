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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFoldersFirst;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;

/**
 * @internal
 */
final class AssetQueryTest extends Unit
{
    public function testOrderFoldersFirstIsAppliedBeforeThePathSort(): void
    {
        $query = new AssetQuery(new AssetSearch());

        $query->orderFoldersFirst()->orderByPath('asc');

        $modifiers = $query->getSearch()->getModifiers();
        $this->assertCount(2, $modifiers);
        $this->assertInstanceOf(OrderByFoldersFirst::class, $modifiers[0]);
        $this->assertInstanceOf(OrderByFullPath::class, $modifiers[1]);
    }
}
