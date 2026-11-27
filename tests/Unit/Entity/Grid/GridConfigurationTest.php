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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Entity\Grid;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;

/**
 * @internal
 */
final class GridConfigurationTest extends Unit
{
    /**
     * Regression test: a configuration saved without a filter (saveFilter = false) never calls setFilter().
     * Reading the filter of such a fresh entity (as SaveConfigurationService does) must yield null
     * instead of failing with "must not be accessed before initialization".
     */
    public function testFilterIsNullUntilSet(): void
    {
        $configuration = new GridConfiguration();

        $this->assertNull($configuration->getFilter());
    }

    public function testFilterReturnsTheValueThatWasSet(): void
    {
        $configuration = new GridConfiguration();
        $configuration->setFilter(['columnFilters' => []]);

        $this->assertSame(['columnFilters' => []], $configuration->getFilter());
    }
}
