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
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constant\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchFolderMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\FilterParameterMapperInterface;
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
        private readonly FilterParameterMapperInterface $filterParameterMapper,
        private readonly PublishServiceInterface $publishService,
        private readonly ElementServiceInterface $elementService,
        private readonly PatchServiceInterface $patchService,
        private readonly UserResolverInterface $userResolver,
        private readonly GridSearchInterface $gridSearch,
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

        $folderId = $validatedParameters->getSubject()->getId();
        $elementType =  $validatedParameters->getSubject()->getType();
        $filters = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_FILTERS->value);

        $result = $this->gridSearch->searchElementsForUser(
            $elementType,
            new GridParameter(
                $folderId,
                [],
                $this->filterParameterMapper->fromArray($filters)
            ),
            $validatedParameters->getUser()
        );

        if (empty($result->getItems())) {
            $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());

            return;
        }

        $jobEnvironmentData = $jobRun->getJob()?->getEnvironmentData();

        foreach ($result->getItems() as $item) {
            $element = $this->elementService->getAllowedElementById(
                $elementType,
                $item->getId(),
                $validatedParameters->getUser()
            );
            $elementId = $element->getId();

            try {
                $this->patchService->patchElement(
                    $element,
                    $elementType,
                    $jobEnvironmentData[$folderId],
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

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_FILTERS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_FILTERS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
    }
}
