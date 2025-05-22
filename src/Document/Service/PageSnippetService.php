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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\ControllerEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\TemplateEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Controller;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Template;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ReflectionException;
use Pimcore\Controller\Config\ControllerDataProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class PageSnippetService implements PageSnippetServiceInterface
{
    public function __construct(
        private ControllerDataProvider $controllerDataProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableControllers(): array
    {
        try {
            $controllerReferences = $this->controllerDataProvider->getControllerReferences();
        } catch (Exception $exception) {
            throw new ReflectionException($exception->getMessage(), $exception);
        }

        $controllers = [];
        foreach ($controllerReferences as $controllerReference) {
            $controller = new Controller($controllerReference);
            $this->eventDispatcher->dispatch(new ControllerEvent($controller), ControllerEvent::EVENT_NAME);

            $controllers[] = $controller;
        }

        return $controllers;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableTemplates(): array
    {
        $templateReferences = $this->controllerDataProvider->getTemplates();
        $templates = [];
        foreach ($templateReferences as $templateReference) {
            $template = new Template($templateReference);
            $this->eventDispatcher->dispatch(new TemplateEvent($template), TemplateEvent::EVENT_NAME);

            $templates[] = $template;
        }

        return $templates;
    }
}
