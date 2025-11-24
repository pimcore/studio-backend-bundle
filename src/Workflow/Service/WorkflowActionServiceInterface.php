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

use Pimcore\Bundle\StudioBackendBundle\Workflow\Response\ActionSubmissionResponse;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
interface WorkflowActionServiceInterface
{
    public function submitAction(
        UserInterface $user,
        SubmitAction $parameters
    ): ActionSubmissionResponse;

    public function enrichActionNotes(
        GlobalAction|Transition $action,
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
