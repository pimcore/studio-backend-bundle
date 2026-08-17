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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;

/**
 * @internal
 */
final readonly class ElementSaveService implements ElementSaveServiceInterface
{
    public function __construct(
        private SynchronousProcessingServiceInterface $synchronousProcessingService,
        private SecurityServiceInterface $securityService,
        private Manager $workflowManager
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function save(ElementInterface $element, UserInterface $user, ?string $task = null): void
    {
        $this->synchronousProcessingService->enable();
        $element->setUserModification($user->getId());

        if ($task === null) {
            $element->save();

            return;
        }

        $this->processTask($element, $user, $task);
    }

    /**
     * @throws Exception
     */
    private function processTask(ElementInterface $element, UserInterface $user, string $task): void
    {
        match ($task) {
            ElementSaveTasks::SAVE->value => $element->save(),
            ElementSaveTasks::AUTOSAVE->value, ElementSaveTasks::VERSION->value =>
                $this->processVersionTasks($element, $user, $task),
            ElementSaveTasks::PUBLISH->value, ElementSaveTasks::UNPUBLISH->value =>
                $this->processPublishTasks($element, $user, $task),
            default => null
        };
    }

    /**
     * @throws Exception
     */
    private function processVersionTasks(ElementInterface $element, UserInterface $user, string $task): void
    {
        if (!$element instanceof Concrete && !$element instanceof PageSnippet) {
            return;
        }

        if ($element instanceof Concrete) {
            $element->setOmitMandatoryCheck(true);
        }

        $autoSave = $task === ElementSaveTasks::AUTOSAVE->value;
        $element->saveVersion(true, true, null, $autoSave);
        if ($autoSave) {
            return;
        }

        $element->deleteAutoSaveVersions($user->getId());
    }

    /**
     * @throws Exception
     */
    private function processPublishTasks(ElementInterface $element, UserInterface $user, string $task): void
    {
        if (!$element instanceof Concrete && !$element instanceof Document) {
            return;
        }

        if ($task === ElementSaveTasks::PUBLISH->value) {
            $this->publishElement($element, $user);

            return;
        }

        $this->unpublishElement($element, $user);
    }

    /**
     * @throws Exception|ForbiddenException
     */
    private function publishElement(Concrete|Document $element, UserInterface $user): void
    {
        $this->securityService->hasElementPermission($element, $user, ElementPermissions::PUBLISH_PERMISSION);
        $this->hasWorkflowPermission($element, ElementPermissions::PUBLISH_PERMISSION);
        $element->setPublished(true);
        $element->save();
        $element->deleteAutoSaveVersions($user->getId());
    }

    /**
     * @throws Exception|ForbiddenException
     */
    private function unpublishElement(Concrete|Document $element, UserInterface $user): void
    {
        $this->securityService->hasElementPermission($element, $user, ElementPermissions::UNPUBLISH_PERMISSION);
        $this->hasWorkflowPermission($element, ElementPermissions::UNPUBLISH_PERMISSION);
        if ($element instanceof Concrete) {
            $element->setOmitMandatoryCheck(true);
        }
        $element->setPublished(false);
        $element->save();
    }

    /**
     * @throws ForbiddenException
     */
    private function hasWorkflowPermission(Concrete|Document $element, string $permission): void
    {
        if ($this->workflowManager->isDeniedInWorkflow($element, $permission)) {
            throw new ForbiddenException();
        }
    }
}
