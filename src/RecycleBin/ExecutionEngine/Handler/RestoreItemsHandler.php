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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Handler;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Messages\RestoreItemsMessage;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class RestoreItemsHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly RecycleBinServiceInterface $recycleBinService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly UserResolverInterface $userResolver,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(RestoreItemsMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $validatedParameters = $this->validateJobParameters($message, $jobRun, $this->userResolver);
        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $items = $this->extractConfigFieldFromJobStepConfig(
            $message,
            StepConfig::ITEMS_TO_RESTORE->value
        );
        $totalItems = count($items);
        $stepName = $this->getJobStep($message)->getName();

        foreach ($items as $id) {
            $relocatedItems = [];

            try {
                $relocatedItems = $this->recycleBinService->restoreItem($id, $user);
            } catch (Exception $e) {
                $this->abort($this->getAbortData(
                    Config::RECYCLE_BIN_RESTORE_FAILED->value,
                    ['id' => $id, 'message' => $e->getMessage()]
                ));
            }

            $this->addRelocatedItems($jobRun, $relocatedItems);
            $this->updateProgress(
                $this->publishService,
                $this->userTopicService,
                $jobRun,
                $stepName,
                $totalItems,
                $totalItems,
            );
        }
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ITEMS_TO_RESTORE->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ITEMS_TO_RESTORE->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
    }

    /**
     * @param RelocatedItem[] $relocatedItems
     */
    private function addRelocatedItems(JobRun $jobRun, array $relocatedItems): void
    {
        if ($relocatedItems === []) {
            return;
        }

        $contextKey = JobRunContext::RELOCATED_ITEMS->value;
        $storedItems = $jobRun->getContext()[$contextKey] ?? [];
        foreach ($relocatedItems as $relocatedItem) {
            $storedItems[] = [
                'id' => $relocatedItem->getId(),
                'elementType' => $relocatedItem->getElementType(),
                'path' => $relocatedItem->getPath(),
            ];
        }

        $this->updateJobRunContextValues($jobRun, [$contextKey => $storedItems]);
    }
}
