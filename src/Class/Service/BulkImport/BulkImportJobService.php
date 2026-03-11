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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\AutomationAction\Messenger\Messages\BulkImportCleanupMessage;
use Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\AutomationAction\Messenger\Messages\BulkImportMessage;
use Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\BulkImportParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class BulkImportJobService implements BulkImportJobServiceInterface
{
    private const array STEP_NAME_MAP = [
        ClassDefinitionType::FieldCollection->value => JobSteps::FIELD_COLLECTION_IMPORTING,
        ClassDefinitionType::ClassDefinition->value => JobSteps::CLASS_IMPORTING,
        ClassDefinitionType::CustomLayout->value => JobSteps::CUSTOM_LAYOUT_IMPORTING,
        ClassDefinitionType::ObjectBrick->value => JobSteps::OBJECT_BRICK_IMPORTING,
    ];

    public function __construct(
        private SecurityServiceInterface $securityService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private BulkImportFileServiceInterface $bulkImportFileService,
        private BulkImportDataResolver $bulkImportDataResolver,
    ) {
    }

    public function importItems(string $fileId, BulkImportParameters $parameters): int
    {
        if (empty($parameters->getItems())) {
            throw new ApiInvalidArgumentException(
                'No items provided for import'
            );
        }

        $fileData = $this->bulkImportFileService->readFileData($fileId);
        $requestedItems = $this->bulkImportDataResolver->buildRequestedItemsIndex(
            $parameters->getItems()
        );
        $requestedTypes = $this->bulkImportDataResolver->resolveRequestedTypes(
            $fileData,
            $requestedItems
        );

        if (empty($requestedTypes)) {
            throw new ApiInvalidArgumentException(
                'None of the requested items were found in the import file'
            );
        }

        $user = $this->securityService->getCurrentUser();
        if (!$user instanceof UserInterface) {
            throw new EnvironmentException('Could not resolve current user');
        }

        $steps = [];
        foreach (ClassDefinitionType::importOrder() as $type) {
            if (!isset($requestedTypes[$type->value])) {
                continue;
            }

            $stepName = self::STEP_NAME_MAP[$type->value];
            $steps[] = new JobStep(
                $stepName->value,
                BulkImportMessage::class,
                '',
                [StepConfig::IMPORT_TYPE->value => $type->value],
                SelectionProcessingMode::ONCE,
            );
        }

        $steps[] = new JobStep(
            JobSteps::BULK_IMPORT_CLEANUP->value,
            BulkImportCleanupMessage::class,
            '',
            [],
            SelectionProcessingMode::ONCE,
        );

        $job = new Job(
            Jobs::BULK_IMPORT_CLASS_DEFINITIONS->value,
            $steps,
            [],
            [
                EnvironmentVariables::BULK_IMPORT_FILE_ID->value => $fileId,
                EnvironmentVariables::BULK_IMPORT_ITEMS->value => $parameters->getItems(),
            ],
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value,
        );

        return $jobRun->getId();
    }
}
