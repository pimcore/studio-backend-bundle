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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\MappedParameter\HideJobRunsParameter;

/**
 * @internal
 */
interface ExecutionEngineServiceInterface
{
    /**
     * @throws UserNotFoundException
     */
    public function listRunningJobRuns(): array;

    /**
     * @throws DatabaseException|ForbiddenException|NotFoundException
     */
    public function abortAction(int $jobRunId): void;

    public function hideAction(HideJobRunsParameter $parameter): void;

    /**
     * @throws ForbiddenException|UserNotFoundException|NotFoundException
     */
    public function validateJobRun(int $jobRunId): void;
}
