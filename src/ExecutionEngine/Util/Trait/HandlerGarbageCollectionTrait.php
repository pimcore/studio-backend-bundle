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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait;

use Pimcore\Bundle\StaticResolverBundle\Lib\PimcoreResolverInterface;

/**
 * @internal
 */
trait HandlerGarbageCollectionTrait
{
    private const int GARBAGE_COLLECTION_INTERVAL = 50;

    private function collectGarbagePeriodically(
        PimcoreResolverInterface $pimcoreResolver,
        int $processedElements,
        int $interval = self::GARBAGE_COLLECTION_INTERVAL
    ): void {
        if ($interval < 1 || $processedElements === 0 || $processedElements % $interval !== 0) {
            return;
        }

        $pimcoreResolver->collectGarbage();
    }
}
