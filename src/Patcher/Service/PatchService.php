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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Service;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\PatchFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchFolderMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function count;

/**
 * @internal
 */
final readonly class PatchService implements PatchServiceInterface
{
    public function __construct(
        private SynchronousProcessingServiceInterface $synchronousProcessingService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private ElementServiceInterface $elementService,
        private AdapterLoaderInterface $adapterLoader
    ) {
    }

    /**
     * @throws AccessDeniedException|ElementSavingFailedException|NotFoundException|InvalidArgumentException
     */
    public function patch(
        string $elementType,
        array $patchData,
        UserInterface $user,
    ): ?int {
        if (count($patchData) > 1) {
            return $this->patchAsynchronously($elementType, $patchData, $user);
        }

        $element = $this->elementService->getAllowedElementById($elementType, $patchData[0]['id'], $user);
        $this->patchElement($element, $elementType, $patchData[0], $user);

        return null;
    }

    public function patchFolder(
        string $elementType,
        PatchFolderParameter $patchFolderParameter,
        UserInterface $user,
    ): ?int {
        $job = new Job(
            name: Jobs::PATCH_ELEMENTS->value,
            steps: [
                new JobStep(
                    JobSteps::ELEMENT_FOLDER_PATCHING->value,
                    PatchFolderMessage::class,
                    '',
                    ['filters' => $patchFolderParameter->getFilters()]
                ),
            ],
            selectedElements: array_map(
                static fn (array $data) => new ElementDescriptor(
                    $elementType,
                    $data['folderId']
                ),
                $patchFolderParameter->getData()
            ),
            environmentData: array_column($patchFolderParameter->getData(), null, 'folderId'),
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    /**
     * @throws ElementSavingFailedException
     */
    public function patchElement(
        ElementInterface $element,
        string $elementType,
        array $elementPatchData,
        UserInterface $user,
    ): void {
        try {
            $adapters = $this->adapterLoader->loadAdapters($elementType);
            foreach ($adapters as $adapter) {
                $adapter->patch($element, $elementPatchData);
            }

            $this->synchronousProcessingService->enable();
            $element->setUserModification($user->getId());
            $element->save();
        } catch (Exception $exception) {
            throw new ElementSavingFailedException($element->getId(), $exception->getMessage());
        }
    }

    private function patchAsynchronously(
        string $elementType,
        array $patchData,
        UserInterface $user,
    ): int {
        $job = new Job(
            name: Jobs::PATCH_ELEMENTS->value,
            steps: [
                new JobStep(JobSteps::ELEMENT_PATCHING->value, PatchMessage::class, '', []),
            ],
            selectedElements: array_map(
                static fn (array $data) => new ElementDescriptor(
                    $elementType,
                    $data['id']
                ),
                $patchData
            ),
            environmentData: array_column($patchData, null, 'id'),
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }
}
