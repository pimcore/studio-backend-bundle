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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\RenderletParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\RenderletService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

/**
 * @internal
 */
final class RenderletServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testRenderForwardsParentDocumentAsContentDocumentAttribute(): void
    {
        $parentDocument = $this->makeEmpty(PageSnippet::class, [
            'getTemplate' => null,
            'getProperty' => null,
        ]);

        $capturedAttributes = null;
        $service = $this->createService($parentDocument, $capturedAttributes);

        $parameter = new RenderletParameter(
            id: 5,
            type: ElementTypes::TYPE_DATA_OBJECT,
            controller: 'App\Controller\MyController::renderAction',
            parentDocumentId: 38,
        );

        $service->render($parameter, []);

        $this->assertSame($parentDocument, $capturedAttributes[DynamicRouter::CONTENT_KEY] ?? null);
    }

    /**
     * @throws Exception
     */
    private function createService(PageSnippet $parentDocument, ?array &$capturedAttributes = null): RenderletService
    {
        $capturedAttributes = null;

        $targetElement = $this->makeEmpty(ElementInterface::class);

        $elementService = $this->makeEmpty(ElementServiceInterface::class, [
            'getAllowedElementById' => function (string $type, int $id, UserInterface $user) use ($targetElement, $parentDocument) {
                return $type === ElementTypes::TYPE_DOCUMENT ? $parentDocument : $targetElement;
            },
        ]);

        $editableHandler = $this->makeEmpty(EditableHandler::class, [
            'renderAction' => function (string $controller, array $attributes, array $query) use (&$capturedAttributes) {
                $capturedAttributes = $attributes;

                return '<div></div>';
            },
        ]);

        $actionRenderer = new ActionRenderer(
            new HttpKernelRuntime($this->makeEmpty(FragmentHandler::class))
        );

        return new RenderletService(
            $actionRenderer,
            $editableHandler,
            $elementService,
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(LocaleServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => fn () => $this->makeEmpty(UserInterface::class),
            ]),
        );
    }
}
