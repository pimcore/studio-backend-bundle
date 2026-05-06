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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\RewriteRefMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\ChunkGeneratorTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class ElementReferenceService implements ElementReferenceServiceInterface
{
    use ChunkGeneratorTrait;

    private const int REWRITE_REFERENCES_BATCH_SIZE = 500;

    public function __construct(
        private AssetServiceResolverInterface $assetServiceResolver,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private DocumentServiceResolverInterface $documentServiceResolver,
        private JobExecutionAgentInterface $jobExecutionAgent,
    ) {
    }

    /**
     * @throws Exception
     */
    public function rewriteElementReferences(
        UserInterface $user,
        ElementInterface $element,
        array $rewriteConfiguration,
        array $parameters = []
    ): void {
        match (true) {
            $element instanceof AbstractObject => $this->rewriteDataObjectReferences(
                $user,
                $element,
                $rewriteConfiguration,
                $parameters
            ),
            $element instanceof Asset => $this->rewriteAssetReferences(
                $element,
                $rewriteConfiguration
            ),
            $element instanceof Document => $this->rewriteDocumentReferences(
                $user,
                $element,
                $rewriteConfiguration,
                $parameters
            ),
            default => throw new InvalidElementTypeException($element->getType()),
        };
    }

    public function rewriteReferencesWithExecutionEngine(
        UserInterface $user,
        array $rewriteConfiguration,
        array $ids,
        string $type
    ): int {
        $jobSteps = [];
        foreach ($this->chunkGenerator($ids, self::REWRITE_REFERENCES_BATCH_SIZE) as $batch) {
            $jobSteps[] = new JobStep(
                JobSteps::ELEMENT_REWRITE_REFERENCE->value,
                RewriteRefMessage::class,
                '',
                [
                    StepConfig::ELEMENTS_TO_REWRITE_REFERENCES->value => $batch,
                    StepConfig::ELEMENT_TYPE_TO_REWRITE_REFERENCES->value => $type,
                ],
            );
        }

        $job = new Job(
            name: Jobs::REWRITE_REFERENCES->value,
            steps: $jobSteps,
            environmentData: [
                EnvironmentVariables::REWRITE_CONFIGURATION->value => [$type => $rewriteConfiguration],
                EnvironmentVariables::REWRITE_PARAMETERS->value => [],
            ]
        );
        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    private function rewriteAssetReferences(
        Asset $element,
        array $rewriteConfiguration
    ): void {
        $this->assetServiceResolver->rewriteIds(
            $element,
            $rewriteConfiguration
        );
    }

    /**
     * @throws Exception
     */
    private function rewriteDocumentReferences(
        UserInterface $user,
        Document $element,
        array $rewriteConfiguration,
        array $parameters = [],
    ): void {
        $object = $this->documentServiceResolver->rewriteIds(
            $element,
            $rewriteConfiguration,
            $parameters
        );

        $object->setUserModification($user->getId());
        $object->save();
    }

    /**
     * @throws Exception
     */
    private function rewriteDataObjectReferences(
        UserInterface $user,
        AbstractObject $element,
        array $rewriteConfiguration,
        array $parameters = [],
    ): void {
        $object = $this->dataObjectServiceResolver->rewriteIds(
            $element,
            $rewriteConfiguration,
            $parameters
        );

        $object->setUserModification($user->getId());
        $object->save();
    }
}
