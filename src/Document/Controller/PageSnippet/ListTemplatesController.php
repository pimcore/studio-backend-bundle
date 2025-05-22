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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\PageSnippet;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Template;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\PageSnippetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ReflectionException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListTemplatesController extends AbstractApiController
{
    private const string ROUTE = '/documents/get-available-templates';

    public function __construct(
        SerializerInterface $serializer,
        private readonly PageSnippetServiceInterface $pageSnippetService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ReflectionException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_list_document_available_templates',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_available_templates_list',
        description: 'document_available_templates_list_description',
        summary: 'document_available_templates_list_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_available_templates_list_success_response',
        content: new ItemsJson(Template::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getAvailableTemplates(): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->pageSnippetService->getAvailableTemplates()]);
    }
}
