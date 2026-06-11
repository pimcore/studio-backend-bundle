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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ExecutionEngine\Util\Trait;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\PimcoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerGarbageCollectionTrait;

/**
 * @internal
 */
final class HandlerGarbageCollectionTraitTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testCollectsGarbageOnIntervalMultiplesOnly(): void
    {
        $calls = 0;
        $resolver = $this->createResolver($calls);
        $helper = $this->createTraitHelper();

        for ($processedElements = 1; $processedElements <= 100; $processedElements++) {
            $helper->callCollectGarbagePeriodically($resolver, $processedElements, 50);
        }

        $this->assertSame(2, $calls, 'Garbage collection must run exactly at 50 and 100 processed elements');
    }

    /**
     * @throws Exception
     */
    public function testDoesNotCollectGarbageForZeroProcessedElements(): void
    {
        $calls = 0;
        $resolver = $this->createResolver($calls);

        $this->createTraitHelper()->callCollectGarbagePeriodically($resolver, 0, 50);

        $this->assertSame(0, $calls);
    }

    /**
     * @throws Exception
     */
    public function testDoesNotCollectGarbageForInvalidInterval(): void
    {
        $calls = 0;
        $resolver = $this->createResolver($calls);

        $this->createTraitHelper()->callCollectGarbagePeriodically($resolver, 50, 0);

        $this->assertSame(0, $calls);
    }

    /**
     * @throws Exception
     */
    public function testUsesDefaultInterval(): void
    {
        $calls = 0;
        $resolver = $this->createResolver($calls);
        $helper = $this->createTraitHelper();

        $helper->callCollectGarbagePeriodically($resolver, 49);
        $this->assertSame(0, $calls, 'No garbage collection below the default interval');

        $helper->callCollectGarbagePeriodically($resolver, 50);
        $this->assertSame(1, $calls, 'Garbage collection must run at the default interval of 50');
    }

    /**
     * @throws Exception
     */
    private function createResolver(int &$calls): PimcoreResolverInterface
    {
        return $this->makeEmpty(PimcoreResolverInterface::class, [
            'collectGarbage' => function () use (&$calls): void {
                $calls++;
            },
        ]);
    }

    private function createTraitHelper(): object
    {
        return new class {
            use HandlerGarbageCollectionTrait;

            public function callCollectGarbagePeriodically(
                PimcoreResolverInterface $pimcoreResolver,
                int $processedElements,
                ?int $interval = null,
            ): void {
                if ($interval === null) {
                    $this->collectGarbagePeriodically($pimcoreResolver, $processedElements);

                    return;
                }

                $this->collectGarbagePeriodically($pimcoreResolver, $processedElements, $interval);
            }
        };
    }
}
