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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\WorkflowDependencyMissingException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool\Console;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowGraphService implements WorkflowGraphServiceInterface
{
    private const PHP_EXECUTABLE = 'php';

    private const DOT_EXECUTABLE = 'dot';

    public function getGraph(
        ElementInterface $element,
        WorkflowInterface $workflow,
        string $format
    ): string
    {
        $marking = $workflow->getMarking($element);

        $params = [
            'NAME' => $workflow->getName(),
            'PLACES' => implode(' ', array_keys($marking->getPlaces())),
            'DOT' => $this->getExecutable(self::DOT_EXECUTABLE),
        ];

        $cmd = $this->getExecutable(self::PHP_EXECUTABLE) . ' ' .
            PIMCORE_PROJECT_ROOT .
            '/bin/console pimcore:workflow:dump ${NAME} ${PLACES} | ${DOT} -T'.$format;

        Console::addLowProcessPriority($cmd);
        $process = Process::fromShellCommandline($cmd);
        $process->run(null, $params);

        return $process->getOutput();
    }

    private function getExecutable(string $executable): string
    {
        try {
            $consoleExecutable = Console::getExecutable($executable);

            if (!$consoleExecutable) {
                throw new WorkflowDependencyMissingException(
                    $executable
                );
            }

            return $consoleExecutable;
        } catch (Exception) {
            throw new WorkflowDependencyMissingException(
                $executable
            );
        }

    }
}
