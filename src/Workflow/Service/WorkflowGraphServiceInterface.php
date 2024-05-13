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
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
interface WorkflowGraphServiceInterface
{
    public function getGraphFromGraphvizFile(
        string $graphvizFile,
        string $format
    ): string;


    public function getGraphvizFile(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): string;
}
