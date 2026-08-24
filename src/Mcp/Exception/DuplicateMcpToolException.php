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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Exception;

use LogicException;
use function sprintf;

/**
 * Two registered MCP tools claim the same name. A configuration/programming
 * error surfaced when the registry is built.
 *
 * @internal
 */
final class DuplicateMcpToolException extends LogicException
{
    public function __construct(string $toolName)
    {
        parent::__construct(sprintf('More than one MCP tool is registered under the name "%s".', $toolName));
    }
}
