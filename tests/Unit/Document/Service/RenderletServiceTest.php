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
use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class RenderletServiceTest extends Unit
{
    private array $renderedAttributes = [];

    private array $renderedQuery = [];

    /**
     * Regression test: the renderlet controller must receive its parameters as request attributes in edit mode,
     * the same way as on the frontend (pimcore/studio-ui-bundle#3460).
     *
     * @throws Exception
     */
    public function testRenderPassesRenderletParametersAsAttributes(): void
    {
        $requestParameters = [
            'id' => '42',
            'type' => 'object',
            'subtype' => 'News',
            'controller' => 'App\Controller\NewsController::newsTeaserAction',
            'template' => 'news/teaser.html.twig',
            'height' => '200',
            'teaserType' => 'small',
            '_controller' => 'App\Controller\OtherController::otherAction',
        ];

        $this->getRenderletService()->render(
            new RenderletParameter(
                42,
                'object',
                'App\Controller\NewsController::newsTeaserAction',
                null,
                'news/teaser.html.twig'
            ),
            $requestParameters
        );

        $this->assertSame(42, $this->renderedAttributes['id']);
        $this->assertSame('object', $this->renderedAttributes['type']);
        $this->assertSame('News', $this->renderedAttributes['subtype']);
        $this->assertSame('news/teaser.html.twig', $this->renderedAttributes['template']);
        $this->assertSame('renderlet', $this->renderedAttributes['pimcore_request_source']);
        $this->assertSame('200', $this->renderedAttributes['height']);
        $this->assertSame('small', $this->renderedAttributes['teaserType']);
        $this->assertArrayNotHasKey('controller', $this->renderedAttributes);
        $this->assertArrayNotHasKey('_controller', $this->renderedAttributes);

        $this->assertSame('42', $this->renderedQuery['id']);
        $this->assertSame('small', $this->renderedQuery['teaserType']);
        $this->assertArrayNotHasKey('controller', $this->renderedQuery);
    }

    /**
     * @throws Exception
     */
    private function getRenderletService(): RenderletService
    {
        $editableHandler = $this->makeEmpty(EditableHandler::class, [
            'renderAction' => function (string $controller, array $attributes, array $query): string {
                $this->renderedAttributes = $attributes;
                $this->renderedQuery = $query;

                return '';
            },
        ]);

        return new RenderletService(
            $this->makeEmpty(ActionRenderer::class),
            $editableHandler,
            $this->makeEmpty(ElementServiceInterface::class, [
                'getAllowedElementById' => $this->makeEmpty(Concrete::class),
            ]),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(LocaleServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]),
        );
    }
}
