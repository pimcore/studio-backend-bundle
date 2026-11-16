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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowStatusHydrator;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Place\PlaceConfig;

/**
 * @internal
 */
final class WorkflowStatusHydratorTest extends Unit
{
    public function testHydratesThePlaceConfig(): void
    {
        $placeConfig = new PlaceConfig(
            'review_license',
            ['label' => 'Review License', 'color' => '#ffa500', 'colorInverted' => true, 'visibleInHeader' => true],
            $this->makeEmpty(ExpressionService::class),
            'asset_workflow'
        );

        $status = (new WorkflowStatusHydrator())->hydrate($placeConfig, 'layout-1');

        $this->assertSame('#ffa500', $status->getColor());
        $this->assertTrue($status->getColorInverted());
        $this->assertSame('review_license', $status->getTitle());
        $this->assertSame('Review License', $status->getLabel());
        $this->assertSame('layout-1', $status->getLayoutId());
        $this->assertTrue($status->isVisibleInDetail());
    }

    public function testHydratesWithoutLayout(): void
    {
        $placeConfig = new PlaceConfig(
            'start',
            ['visibleInHeader' => false],
            $this->makeEmpty(ExpressionService::class),
            'asset_workflow'
        );

        $status = (new WorkflowStatusHydrator())->hydrate($placeConfig);

        $this->assertNull($status->getLayoutId());
        $this->assertFalse($status->isVisibleInDetail());
        $this->assertSame('start', $status->getLabel());
    }
}
