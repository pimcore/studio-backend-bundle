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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Util;

use function is_int;
use function is_string;
use function parse_url;

/**
 * Extracts the host (with port, when present) of a redirect URI for display on
 * the consent screen — the trustworthy "where does access go" signal, as
 * opposed to the client's self-chosen name.
 *
 * @internal
 */
final class RedirectHost
{
    public static function fromUri(?string $uri): ?string
    {
        if ($uri === null || $uri === '') {
            return null;
        }

        $host = parse_url($uri, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $port = parse_url($uri, PHP_URL_PORT);

        return is_int($port) ? $host . ':' . $port : $host;
    }
}
