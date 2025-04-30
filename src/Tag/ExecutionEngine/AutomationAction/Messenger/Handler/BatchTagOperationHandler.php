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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\ExecutionEngine\AutomationAction\Messenger\Messages\BatchTagOperationMessage;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Util\Constant\BatchOperations;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function sprintf;

/**
 * @internal
 */
#[AsMessageHandler]
final class BatchTagOperationHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly TagServiceInterface $tagService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(BatchTagOperationMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $validatedParameters = $this->validateFullParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                EnvironmentVariables::BATCH_TAG_OPERATION->value,
                EnvironmentVariables::TAG_IDS->value,
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $element = $validatedParameters->getSubject();
        $environmentVariables = $validatedParameters->getEnvironmentData();
        $operation = $environmentVariables[EnvironmentVariables::BATCH_TAG_OPERATION->value];

        try {
            $parameters = new BatchCollectionParameters(
                $element->getType(),
                [$element->getId()],
                $environmentVariables[EnvironmentVariables::TAG_IDS->value]
            );
            match ($operation) {
                BatchOperations::ASSIGN->value => $this->tagService->batchAssignTagsToElements(
                    $parameters,
                    $user
                ),
                BatchOperations::REPLACE->value => $this->tagService->batchReplaceTagsToElements(
                    $parameters,
                    $user
                ),
                default => throw new Exception(
                    sprintf(
                        'Invalid batch operation %s',
                        $operation
                    )
                ),
            };
        } catch (Exception $exception) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_TAG_OPERATION_FAILED_MESSAGE->value,
                [
                    'id' => $element->getId(),
                    'type' => $element->getType(),
                    'operation' => $operation,
                    'message' => $exception->getMessage(),
                ],
            ));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
