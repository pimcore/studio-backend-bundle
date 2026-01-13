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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\DefinitionConfiguration;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\TextLayoutPreviewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\LayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TextLayoutController extends AbstractApiController
{
    private const string ROUTE = '/class/definition/configuration-view/text-layout/preview';

    public function __construct(
        SerializerInterface $serializer,
        private readonly LayoutServiceInterface $layoutService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_class_definition_get_text_layout_preview', methods: ['GET'])]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_definition_get_text_layout_preview',
        description: 'class_definition_get_text_layout_preview_description',
        summary: 'class_definition_get_text_layout_preview_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[TextFieldParameter(
        name: 'className',
        description: 'Class definition name where layout is defined',
        required: true,
        example: 'Car'
    )]
    #[TextFieldParameter(
        name: 'path',
        description: 'Path to optional object to render the layout with',
        example: '/cars/my-car'
    )]
    #[TextFieldParameter(
        name: 'renderingData',
        description: 'Optional dynamic data to be used for rendering the layout',
        example: '<div>Some HTML data</div>'
    )]
    #[TextFieldParameter(
        name: 'renderingClass',
        description: 'Optional rendering class to be used for rendering the layout',
        example: 'My\\Custom\\Class'
    )]
    #[TextFieldParameter(
        name: 'html',
        description: 'Optional static HTML to be used for rendering the layout',
        example: '<div>Some HTML data</div>'
    )]
    #[SuccessResponse(
        description: 'class_definition_get_text_layout_preview_success_response',
        content: [new MediaType('text/html')],
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value, 'text-layout-preview.html')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getPreview(#[MapQueryString] TextLayoutPreviewParameters $parameter): Response
    {

        return new Response($this->layoutService->getTextLayoutPreview($parameter));
    }
}
