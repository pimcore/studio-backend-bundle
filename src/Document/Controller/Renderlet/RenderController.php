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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\Renderlet;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\RenderletParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\RenderletServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class RenderController extends AbstractApiController
{
    private const string ROUTE = '/documents/renderlet/render';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RenderletServiceInterface $renderletService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws UserNotFoundException
     * @throws NotFoundException
     */
    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_render_document_renderlet', methods: ['GET'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_renderlet_render',
        description: 'document_renderlet_render_description',
        summary: 'document_renderlet_render_summary',
        tags: [Tags::Documents->value]
    )]
    #[IdParameter(description: 'ElementId of the renderlet element')]
    #[TextFieldParameter(
        name: 'type',
        description: 'Type of the renderlet element.',
        required: true,
        example: ElementTypes::TYPE_DATA_OBJECT
    )]
    #[TextFieldParameter(
        name: 'controller',
        description: 'Renderlet controller action',
        required: true,
        example: 'App/Controller\Renderlet\MyRenderletController::renderAction'
    )]
    #[SuccessResponse(
        description: 'document_renderlet_render_success_response',
        content: [new MediaType('text/html')],
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value, 'renderlet.html.twig')]
    )]
    #[IdParameter(description: 'Parent document id of the renderlet', namePrefix: 'parentDocument')]
    #[TextFieldParameter(
        name: 'template',
        description: 'Renderlet template',
        required: false,
        example: 'App/Template/Renderlet/my_template.html.twig'
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function renderRenderlet(#[MapQueryString] RenderletParameter $parameter, Request $request): Response
    {

        return new Response($this->renderletService->render(
            $parameter,
            $request->query->all()
        ));
    }
}
