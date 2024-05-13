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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Service;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Dumper\GraphvizDumper;
use Pimcore\Workflow\Dumper\StateMachineGraphvizDumper;
use Pimcore\Workflow\Manager;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowGraphService implements WorkflowGraphServiceInterface
{
    public function __construct(
        private Manager $workflowManager,
        private GraphvizDumper $graphvizDumper,
        private StateMachineGraphvizDumper $stateMachineGraphvizDumper
    )
    {
    }

    public function getGraphFromGraphvizFile(
        string $graphvizFile,
        string $format
    ): string
    {
        $process = Process::fromShellCommandline("dot -T$format");
        $process->setInput($graphvizFile);
        $process->run();

        return $process->getOutput();
    }


    public function getGraphvizFile(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): string
    {
        $dumper = $this->graphvizDumper;
        $configuration = $this->workflowManager->getWorkflowConfig($workflow->getName());
        if ($configuration->getType() === 'state_machine') {
            $dumper = $this->stateMachineGraphvizDumper;
        }
        $marking = $this->markGraphPlaces(array_keys($workflow->getMarking($element)->getPlaces()));

        return $dumper->dump(
            $workflow->getDefinition(),
            $marking,
            ['workflowName' => $workflow->getName()]
        );
    }

    private function markGraphPlaces(array $places): Marking {
        $marking = new Marking();
        foreach ($places as $place) {
            $marking->mark($place);
        }
        return $marking;
    }

}