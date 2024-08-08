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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\ElementBinMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\ElementDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class DeleteService implements DeleteServiceInterface
{
    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent
    ) {
    }

    public function deleteElementsWithExecutionEngine(
        ElementInterface $element,
        UserInterface $user,
        string $elementType,
        array $childrenIds,
        bool $useRecycleBin
    ): int {
        $jobSteps = [];
        if ($useRecycleBin) {
            $jobSteps[] = new JobStep(
                JobSteps::ELEMENT_RECYCLING->value,
                ElementBinMessage::class,
                '',
                []
            );
        }

        $jobSteps = array_merge(
            $jobSteps,
            array_map(
                static fn (int $id) => new JobStep(
                    JobSteps::ELEMENT_DELETION->value,
                    ElementDeleteMessage::class,
                    '',
                    [self::ELEMENT_TO_DELETE => $id]
                ),
            $childrenIds
            )
        );

        $jobSteps[] = new JobStep(
            JobSteps::ELEMENT_DELETION->value,
            ElementDeleteMessage::class,
            '',
            [self::ELEMENT_TO_DELETE => $element->getId()]
        );

        $job = new Job(
            name: $this->getJobName($elementType),
            steps: $jobSteps,
            selectedElements:[
                new ElementDescriptor(
                    $elementType,
                    $element->getId()
                ),
            ]
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getJobName(string $type): string
    {
        return match ($type) {
            ElementTypes::TYPE_ASSET => Jobs::DELETE_ASSETS->value,
            ElementTypes::TYPE_DOCUMENT => Jobs::DELETE_DOCUMENTS->value,
            ElementTypes::TYPE_OBJECT => Jobs::DELETE_DATA_OBJECTS->value,
            default => throw new InvalidElementTypeException($type),
        };
    }
}
