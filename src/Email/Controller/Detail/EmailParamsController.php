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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Controller\Detail;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Email\Attribute\Response\Content\ParametersJson;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\EmailLogServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class EmailParamsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly EmailLogServiceInterface $emailLogService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route('/emails/{id}/params', name: 'pimcore_studio_api_emails_log_params', methods: ['GET'])]
    #[IsGranted(UserPermissions::EMAILS->value)]
    #[Get(
        path: self::PREFIX . '/emails/{id}/params',
        operationId: 'email_log_get_params',
        description: 'email_log_get_params_description',
        summary: 'email_log_get_params_summary',
        tags: [Tags::Emails->value]
    )]
    #[SuccessResponse(
        description: 'email_log_get_params_success_response',
        content: new ParametersJson()
    )]
    #[IdParameter(type: ElementTypes::TYPE_EMAIL)]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getEmailLogParams(
        int $id
    ): JsonResponse {

        return $this->jsonResponse(['data' => $this->emailLogService->getEntryParams($id)]);
    }
}
