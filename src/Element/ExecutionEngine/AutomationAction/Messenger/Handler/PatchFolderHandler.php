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

namespace Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchFolderMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class PatchFolderHandler extends AbstractHandler
{
    use ElementProviderTrait;
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementSearchServiceInterface $elementSearchService,
        private readonly PatchServiceInterface $patchService,
        private readonly PublishServiceInterface $publishService,
        private readonly ElementServiceInterface $elementService,
        private readonly UserResolverInterface $userResolver,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(PatchFolderMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $validatedParameters = $this->validateFullParameters(
            $message,
            $jobRun,
            $this->userResolver,
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $elementType =  $validatedParameters->getSubject()->getType();

        $folder = $this->elementSearchService->getElementById(
            $elementType,
            $validatedParameters->getSubject()->getId(),
            $validatedParameters->getUser(),
        );

        if (!$folder || $folder->getType() !== 'folder') {
            $this->abort($this->getAbortData(
                Config::ELEMENT_PATCH_FAILED_MESSAGE->value,
                [
                    'type' => 'folder',
                    'id' => $validatedParameters->getSubject()->getId(),
                    'message' => Config::NO_FOLDER_PROVIDED->value,
                ],
            ));
        }

        $childrenIds = $this->elementSearchService->getChildrenIds($elementType, $folder->getFullPath());

        if (empty($childrenIds)) {
            $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());

            return;
        }

        $jobEnvironmentData = $jobRun->getJob()?->getEnvironmentData();

        foreach ($childrenIds as $childId) {
            $element = $this->elementService->getAllowedElementById(
                $elementType,
                $childId,
                $validatedParameters->getUser()
            );
            $elementId = $element->getId();

            try {
                $this->patchService->patchElement(
                    $element,
                    $elementType,
                    $jobEnvironmentData[$folder->getId()],
                    $validatedParameters->getUser()
                );
            } catch (Exception $exception) {
                $this->abort($this->getAbortData(
                    Config::ELEMENT_PATCH_FAILED_MESSAGE->value,
                    [
                        'type' => $elementType,
                        'id' => $elementId,
                        'message' => $exception->getMessage(),
                    ],
                ));
            }

            $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
        }
    }
}
