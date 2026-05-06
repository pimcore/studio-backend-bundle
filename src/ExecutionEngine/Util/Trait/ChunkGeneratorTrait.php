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

use Generator;
use function array_slice;
use function count;

/**
 * @internal
 */
trait ChunkGeneratorTrait
{
    private function chunkGenerator(array $items, int $size): Generator
    {
        $total = count($items);

        for ($i = 0; $i < $total; $i += $size) {
            yield array_slice($items, $i, $size);
        }
    }
}