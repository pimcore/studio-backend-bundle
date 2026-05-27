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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Maintenance;

use Pimcore\Bundle\StudioBackendBundle\Security\Repository\McpAccessTokenRepositoryInterface;
use Pimcore\Maintenance\TaskInterface;
use function time;

/**
 * Deletes expired MCP access tokens. Cheap single DELETE — safe to run every cycle.
 *
 * @internal
 */
final readonly class McpAccessTokenGcTask implements TaskInterface
{
    public function __construct(private McpAccessTokenRepositoryInterface $repository)
    {
    }

    public function execute(): void
    {
        $this->repository->deleteExpired(time());
    }
}
