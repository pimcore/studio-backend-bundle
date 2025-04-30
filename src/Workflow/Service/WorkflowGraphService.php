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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Service;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ConsoleExecutableTrait;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool\Console;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowGraphService implements WorkflowGraphServiceInterface
{
    use ConsoleExecutableTrait;

    private const PHP_EXECUTABLE = 'php';

    private const DOT_EXECUTABLE = 'dot';

    public function getGraph(
        ElementInterface $element,
        WorkflowInterface $workflow,
        string $format
    ): string {
        $marking = $workflow->getMarking($element);

        $params = [
            'NAME' => $workflow->getName(),
            'PLACES' => implode(' ', array_keys($marking->getPlaces())),
            'DOT' => $this->getExecutable(self::DOT_EXECUTABLE, 'workflow graph generation'),
        ];

        $cmd = $this->getExecutable(self::PHP_EXECUTABLE) . ' ' .
            PIMCORE_PROJECT_ROOT .
            '/bin/console pimcore:workflow:dump ${NAME} ${PLACES} | ${DOT} -T'.$format;

        Console::addLowProcessPriority($cmd);
        $process = Process::fromShellCommandline($cmd);
        $process->run(null, $params);

        return $process->getOutput();
    }
}
