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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\FallbackFolderServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RestoreContextInterface;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Core dispatches the pre-save events before it checks the parent, so a missing parent can be replaced here.
 *
 * @internal
 */
final readonly class RestoreFallbackFolderSubscriber implements EventSubscriberInterface
{
    private const string RESTORE_ARGUMENT = 'isRecycleBinRestore';

    public function __construct(
        private FallbackFolderServiceInterface $fallbackFolderService,
        private RestoreContextInterface $restoreContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::PRE_ADD => 'onPreSave',
            AssetEvents::PRE_UPDATE => 'onPreSave',
            DocumentEvents::PRE_ADD => 'onPreSave',
            DocumentEvents::PRE_UPDATE => 'onPreSave',
            DataObjectEvents::PRE_ADD => 'onPreSave',
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
        ];
    }

    /**
     * @throws EnvironmentException|InvalidElementTypeException
     */
    public function onPreSave(ElementEventInterface $event): void
    {
        if (!$event->hasArgument(self::RESTORE_ARGUMENT) || $event->getArgument(self::RESTORE_ARGUMENT) !== true) {
            return;
        }

        $user = $this->restoreContext->getUser();
        $element = $event->getElement();
        if ($user === null || !$this->fallbackFolderService->hasMissingParent($element)) {
            return;
        }

        $this->fallbackFolderService->moveToFallbackFolder($element, $user);
        $this->restoreContext->addRelocatedElement($element);
    }
}
