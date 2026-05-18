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
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Schema\JobRun;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface ExecutionEngineServiceInterface
{
    /**
     * @throws UserNotFoundException
     *
     * @return Collection<JobRun>
     */
    public function listJobRuns(CollectionFilterParameter $parameter): Collection;

    /**
     * @throws DatabaseException|ForbiddenException|NotFoundException
     */
    public function abortAction(int $jobRunId): void;

    public function hideAction(HideJobRunsParameter $parameter): void;

    public function hideJobRun(int $jobRunId): void;

    /**
     * @throws ForbiddenException|UserNotFoundException|NotFoundException
     */
    public function validateJobRun(int $jobRunId): void;
}
