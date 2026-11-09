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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Util;

use Symfony\Component\Mercure\Jwt\Grant;

/**
 * @internal
 */
final class Grants
{
    /**
     * @param string[] $subscribe
     * @param string[] $publish
     *
     * @return Grant[]
     */
    public static function fromTopics(array $subscribe, array $publish): array
    {
        $grants = [];
        if ($subscribe !== []) {
            $grants[] = new Grant([Grant::ACTION_SUBSCRIBE], array_values($subscribe));
        }

        if ($publish !== []) {
            $grants[] = new Grant([Grant::ACTION_PUBLISH], array_values($publish));
        }

        return $grants;
    }
}
