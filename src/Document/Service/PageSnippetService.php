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
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\RenderBlockEditmodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\TemplateEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\RenderAreaBlockParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Controller;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\RenderAreaBlockData;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Template;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ReflectionException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Controller\Config\ControllerDataProvider;
use Pimcore\Document\Editable\Block\BlockStateStack;
use Pimcore\Document\Editable\EditmodeEditableDefinitionCollector;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Document\Editable\Areablock;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\EditableRenderer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use function sprintf;

/**
 * @internal
 */
final readonly class PageSnippetService implements PageSnippetServiceInterface
{
    public function __construct(
        private BlockStateStack $blockStateStack,
        private ControllerDataProvider $controllerDataProvider,
        private DocumentServiceInterface $documentService,
        private DocumentResolver $documentResolver,
        private EditmodeEditableDefinitionCollector $definitionCollector,
        private EditableRenderer $editableRenderer,
        private Environment $twig,
        private EventDispatcherInterface $eventDispatcher,
        private LocaleServiceInterface $localeService,
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
    public function renderAreaBlock(
        int $documentId,
        Request $request,
        RenderAreaBlockParameter $parameter
    ): RenderAreaBlockData {
        $document = $this->getPageSnippet($documentId);
        $this->blockStateStack->loadArray($parameter->getBlockStateStack());

        $document->setEditables([]);
        $this->documentResolver->setDocument($request, $document);

        $this->twig->addGlobal('document', $document);
        $this->twig->addGlobal('editmode', true);

        $request->attributes->set(EditmodeResolver::ATTRIBUTE_EDITMODE, true);
        $this->localeService->setLocale($document->getProperty('language'));
        $html = $this->getHtmlCode($document, $parameter);
        $data = new RenderAreaBlockData(
            $this->definitionCollector->getDefinitions(),
            $html
        );

        $this->eventDispatcher->dispatch($data, RenderBlockEditmodeEvent::EVENT_NAME);

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getPageSnippet(int $id): PageSnippet
    {
        $document = $this->documentService->getDocumentElement($this->securityService->getCurrentUser(), $id);

        if (!$document instanceof PageSnippet) {
            throw new InvalidArgumentException(sprintf('Document with id %d is not a PageSnippet', $id));
        }

        return clone $document;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getHtmlCode(PageSnippet $document, RenderAreaBlockParameter $parameter): string
    {
        $areaBlockConfig = $parameter->getAreaBlockConfig();
        $realName = $parameter->getRealName();

        try {
            $block = $this->editableRenderer->getEditable(
                $document,
                'areablock',
                $realName,
                $areaBlockConfig,
                true
            );
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                sprintf('Cannot get areablock editable for realName (%s): %s', $realName, $e->getMessage()),
                $e
            );
        }

        if (!$block instanceof Areablock) {
            throw new InvalidArgumentException(
                sprintf('Editable with realName (%s) is not an areablock', $realName)
            );
        }

        $block->setRealName($realName);
        $block->setEditmode(true);
        $block->setDataFromEditmode($parameter->getAreaBlockData());

        try {
            return trim($block->renderIndex($parameter->getIndex(), true));
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot render areablock with realName (%s) at index (%d): %s',
                    $realName,
                    $parameter->getIndex(),
                    $e->getMessage()
                ),
                $e
            );
        }
    }
}
