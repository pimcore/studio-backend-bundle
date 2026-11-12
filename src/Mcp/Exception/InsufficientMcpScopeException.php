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

use RuntimeException;
use function sprintf;

/**
 * @internal
 */
final class InsufficientMcpScopeException extends RuntimeException
{
    public function __construct(string $tool, string $requiredScope)
    {
        parent::__construct(
            sprintf('The access token lacks the scope "%s" required to call the tool "%s".', $requiredScope, $tool)
        );
    }
}
