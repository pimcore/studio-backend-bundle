<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Exception\NotFoundException as CoreNotFoundException;

/**
 * @internal
 */
final readonly class ExecutionEngineService implements ExecutionEngineServiceInterface
{
    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
    ) {

    }

    public function abortAction(
        int $jobRunId,
    ): void {
       $this->validateJobRun($jobRunId);

        try {
            $this->jobExecutionAgent->cancelJobRun($jobRunId);
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf(
                    'Failed to abort job run: %s',
                    $e->getMessage()
                )
            );
        }
    }

    public function validateJobRun(int $jobRunId): void
    {
        try {
            $allowed = $this->jobExecutionAgent->isInteractionAllowed(
                $jobRunId,
                $this->securityService->getCurrentUser()->getId()
            );
        } catch (CoreNotFoundException) {
            throw new NotFoundException('JobRun', $jobRunId);
        }

        if (!$allowed) {
            throw new ForbiddenException('Only job owner can access the resource.');
        }
    }
}
