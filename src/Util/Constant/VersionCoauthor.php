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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function sprintf;

/**
 * Mirrors the column widths of `versions`.`coauthorType` and `versions`.`coauthor` in core, so
 * overlong input is rejected at the API boundary instead of being truncated during the save.
 *
 * @internal
 */
final class VersionCoauthor
{
    public const string TYPE_MCP_SERVER = 'mcp-server';

    public const int MAX_TYPE_LENGTH = 50;

    public const int MAX_COAUTHOR_LENGTH = 255;

    /**
     * The type `mcp-server` is reserved for the stamp an MCP server sets itself: a payload outside an MCP call
     * must not claim it.
     *
     * @throws InvalidArgumentException
     */
    public static function assertPayloadMayClaimType(string $key, string $type): void
    {
        if ($type === self::TYPE_MCP_SERVER) {
            throw new InvalidArgumentException(
                sprintf('%s "%s" is reserved for MCP servers', $key, self::TYPE_MCP_SERVER)
            );
        }
    }
}
