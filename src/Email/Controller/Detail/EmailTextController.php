<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Email\Controller\Detail;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\EmailLogServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\DataJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class EmailTextController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly EmailLogServiceInterface $emailLogService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    #[Route('/emails/{id}/text', name: 'pimcore_studio_api_emails_log_text', methods: ['GET'])]
    #[IsGranted(UserPermissions::EMAILS->value)]
    #[Get(
        path: self::API_PATH . '/emails/{id}/text',
        operationId: 'email_log_get_text',
        description: 'Get E-Mail log entry text by ID',
        summary: 'Get E-Mail log entry text',
        tags: [Tags::Emails->value]
    )]
    #[SuccessResponse(
        content: new DataJson('Email log entry text data.', 'Some email text content')
    )]
    #[IdParameter(type: ElementTypes::TYPE_EMAIL)]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getEmailLogText(
        int $id
    ): JsonResponse {

        return $this->jsonResponse(['data' => $this->emailLogService->getEntryText($id)]);
    }
}
