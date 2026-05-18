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

namespace Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchFolderMessage;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\FilterParameterMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

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
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
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

        $job = $jobRun->getJob();
        if ($job === null) {
            return;
        }

        $selectedElements = $job->getSelectedElements();
        if (empty($selectedElements)) {
            $this->abort($this->getAbortData(Config::NO_ELEMENT_PROVIDED->value));
        }

        $folderDescriptor = $selectedElements[0];
        $folderId = $folderDescriptor->getId();
        $elementType = $folderDescriptor->getType();

        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                ['userId' => $jobRun->getOwnerId()]
            ));
        }

        $filters = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_FILTERS->value);
        $classId = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_CLASS_ID->value);
        $filters = $this->filterParameterMapper->fromArray($filters);
        if ($classId !== '') {
            $filters->setClassId($classId);
        }

        $elementIds = $this->gridSearch->searchElementIdsForUser(
            $elementType,
            new GridParameter($folderId, [], $filters),
            $user
        );

        if (empty($elementIds)) {
            $this->updateProgress(
                $this->publishService,
                $this->userTopicService,
                $jobRun,
                $this->getJobStep($message)->getName()
            );

            return;
        }

        $newSelectedElements = array_map(
            static fn ($id) => new ElementDescriptor($elementType, $id),
            $elementIds
        );

        $this->updateProgress(
            $this->publishService,
            $this->userTopicService,
            $jobRun,
            $this->getJobStep($message)->getName()
        );

        $jobRun->setTotalElements(count($elementIds));
        $this->setSelectedElementsForNextJobStep($jobRun, $newSelectedElements);
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_FILTERS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_FILTERS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_CLASS_ID->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_CLASS_ID->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
    }
}
