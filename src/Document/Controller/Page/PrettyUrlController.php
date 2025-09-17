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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\Page;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\CheckPrettyUrlParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\PageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PrettyUrlController extends AbstractApiController
{
    private const string ROUTE = '/documents/{id}/page/check-pretty-url';

    public function __construct(
        private readonly PageServiceInterface $pageService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|ValidationFailedException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_page_check_pretty_url', methods: ['POST'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_page_check_pretty_url',
        description: 'document_page_check_pretty_url_description',
        summary: 'document_page_check_pretty_url_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(description: 'document_page_check_pretty_url_success_response')]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT, name: 'id')]
    #[ReferenceRequestBody(CheckPrettyUrlParameters::class)]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function checkPrettyUrl(
        int $id,
        #[MapRequestPayload] CheckPrettyUrlParameters $parameters
    ): Response {
        $this->pageService->checkPrettyUrl($parameters->getPrettyUrl(), $id);

        return new Response();
    }
}
