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

namespace Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\FolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExecutionEngine\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\FilterParameterMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class FolderCollectionHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ExportServiceInterface $exportService,
        private readonly FilterParameterMapperInterface $filterParameterMapper,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly GridSearchInterface $gridSearch
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(FolderCollectionMessage $message): void
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

        $elementType = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_TYPE->value);
        $columns = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_COLUMNS->value);
        $filters = $this->filterParameterMapper->fromArray(
            $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_FILTERS->value)
        );
        $classId = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_CLASS_ID->value);
        if ($classId !== '') {
            $filters->setClassId($classId);
        }

        $elements = $this->gridSearch->searchElementIdsForUser(
            $elementType,
            new GridParameter($validatedParameters->getSubject()->getId(), $columns, $filters),
            $validatedParameters->getUser()
        );

        if (count($elements) === 0) {
            $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());

            return;
        }

        $config = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_CONFIGURATION->value);
        $childJobRunId = $this->exportService->generateExportFileForElements(
            new ExportParameter($columns, $filters, $config, $elements, $elementType, $classId),
            $this->extractConfigFieldFromJobStepConfig($message, StepConfig::EXPORT_FORMAT->value),
            $validatedParameters->getUser()
        );

        $this->updateJobRunContext($jobRun, JobRunContext::CHILD_JOB_RUN->value, $childJobRunId);

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_TYPE->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_TYPE->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_CLASS_ID->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_CLASS_ID->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
        $this->stepConfiguration->setRequired(StepConfig::EXPORT_FORMAT->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::EXPORT_FORMAT->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_COLUMNS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_COLUMNS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_FILTERS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_FILTERS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_CONFIGURATION->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_CONFIGURATION->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setDefaults([
            StepConfig::CONFIG_CONFIGURATION->value => [],
        ]);
    }
}
