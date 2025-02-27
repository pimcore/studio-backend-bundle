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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class ElementSaveService implements ElementSaveServiceInterface
{
    public function __construct(
        private SynchronousProcessingServiceInterface $synchronousProcessingService,
        private SecurityServiceInterface $securityService
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
        if (!$element instanceof Concrete && !$element instanceof Document) {
            return;
        }

        $element->setOmitMandatoryCheck(true);
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

        $this->handlePublishTasks($element, $user, $task);
        $element->save();
    }

    /**
     * @throws ForbiddenException
     */
    private function handlePublishTasks(Concrete|Document $element, UserInterface $user, string $task): void
    {
        if ($task === ElementSaveTasks::PUBLISH->value) {
            $this->publishElement($element, $user);

            return;
        }

        $this->unpublishElement($element, $user);
    }

    /**
     * @throws ForbiddenException
     */
    private function publishElement(Concrete|Document $element, UserInterface $user): void
    {
        $this->securityService->hasElementPermission($element, $user, ElementPermissions::PUBLISH_PERMISSION);
        $element->deleteAutoSaveVersions($user->getId());
        $element->setPublished(true);
    }

    /**
     * @throws ForbiddenException
     */
    private function unpublishElement(Concrete|Document $element, UserInterface $user): void
    {
        $this->securityService->hasElementPermission($element, $user, ElementPermissions::UNPUBLISH_PERMISSION);
        $element->setOmitMandatoryCheck(true);
        $element->setPublished(false);
    }
}
