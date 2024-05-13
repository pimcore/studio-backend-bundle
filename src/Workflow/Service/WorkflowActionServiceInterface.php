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

use Pimcore\Bundle\StudioBackendBundle\Workflow\Result\ActionSubmissionResult;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\GlobalAction;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
interface WorkflowActionServiceInterface
{
    public function submitAction(
        UserInterface $user,
        SubmitAction $parameters
    ): ActionSubmissionResult;

    public function enrichActionNotes(
        Concrete|Folder $object,
        array $notes
    ): array;

    /**
     * @return GlobalAction[]
     */
    public function getGlobalActions(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): array;
}
