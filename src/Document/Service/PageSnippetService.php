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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ReflectionException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Controller\Config\ControllerDataProvider;
use Pimcore\Model\Document\PageSnippet;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class PageSnippetService implements PageSnippetServiceInterface
{
    public function __construct(
        private ControllerDataProvider $controllerDataProvider,
        private DocumentServiceInterface $documentService,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function setMainDocument(int $documentId, ?string $mainDocumentPath = null): void
    {
        try {
            $snippet = $this->getPageSnippet($documentId);
            $snippet->setEditables([]);
            $snippet->setContentMainDocumentId($mainDocumentPath, true);
            $snippet->saveVersion();
        } catch (Exception $exception) {
            throw new ElementSavingFailedException($documentId, $exception->getMessage(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPageSnippet(int $id): PageSnippet
    {
        $document = $this->documentService->getDocumentElement($this->securityService->getCurrentUser(), $id);

        if (!$document instanceof PageSnippet) {
            throw new InvalidArgumentException(sprintf('Document with id %d is not a PageSnippet', $id));
        }

        return $document;
    }
}
