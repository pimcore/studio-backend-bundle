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
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\RenderletParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Event\DocumentEvents;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use function sprintf;

/**
 * @internal
 */
final readonly class RenderletService implements RenderletServiceInterface
{
    public function __construct(
        private ActionRenderer $actionRenderer,
        private EditableHandler $editableHandler,
        private ElementServiceInterface $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private LocaleServiceInterface $localeService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function render(RenderletParameter $parameter, array $requestParameters): string
    {
        $user = $this->securityService->getCurrentUser();
        $element = $this->elementService->getAllowedElementById(
            $parameter->getElementType(),
            $parameter->getElementId(),
            $user
        );

        $this->dispatchEvent($requestParameters, $element);

        $attributes = $this->getAttributes($parameter, $user);
        if (isset($attributes['_locale'])) {
            $this->localeService->setLocale($attributes['_locale']);
        }
        unset($requestParameters['controller']);

        try {
            $result = $this->editableHandler->renderAction(
                $parameter->getController(),
                $attributes,
                $this->cleanupRequestParameters($requestParameters)
            );
        } catch (Exception $e) {
            throw new EnvironmentException(
                sprintf('An error occurred while rendering the renderlet: %s',
                    $e->getMessage()
                )
            );
        }

        if ($result instanceof Response) {
            throw new EnvironmentException(
                'Render action returned a Response object instead of a string.' .
                'This is not allowed in the Studio Backend via Request.'
            );
        }

        return $result;
    }

    private function dispatchEvent(array $requestParameters, ElementInterface $element): void
    {
        $event = new GenericEvent($this, ['requestParams' => $requestParameters, 'element' => $element]);

        $this->eventDispatcher->dispatch($event, DocumentEvents::EDITABLE_RENDERLET_PRE_RENDER);
    }

    private function getAttributes(RenderletParameter $parameter, UserInterface $user): array
    {
        $attributes = [];

        if ($parameter->getParentDocumentId() !== null) {
            $attributes = $this->getAttributesFromDocument($parameter->getParentDocumentId(), $user);
        }

        if ($parameter->getTemplate()) {
            $attributes[DynamicRouter::CONTENT_TEMPLATE] = $parameter->getTemplate();
        }

        return $attributes;
    }

    private function getAttributesFromDocument(int $documentId, UserInterface $user): array
    {
        $attributes = [];
        $document = $this->elementService->getAllowedElementById(
            ElementTypes::TYPE_DOCUMENT,
            $documentId,
            $user
        );

        if ($document instanceof PageSnippet) {
            $attributes = $this->actionRenderer->addDocumentAttributes($document);
            unset($attributes[DynamicRouter::CONTENT_TEMPLATE]);
        }

        return $attributes;
    }

    private function cleanupRequestParameters(array $requestParameters): array
    {
        foreach (['controller', 'action', 'module', 'bundle'] as $key) {
            if (isset($requestParameters[$key])) {
                unset($requestParameters[$key]);
            }
        }

        return $requestParameters;
    }
}
