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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Email\Attribute\Request\ForwardEmailRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailAddressParameter;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\EmailSendServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
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
final class ForwardController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly EmailSendServiceInterface $emailSendService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    #[Route('/emails/{id}/forward', name: 'pimcore_studio_api_emails_forward', methods: ['POST'])]
    #[IsGranted(UserPermissions::EMAILS->value)]
    #[Post(
        path: self::PREFIX . '/emails/{id}/forward',
        operationId: 'email_log_forward_by_id',
        description: 'email_log_forward_by_id_description',
        summary: 'email_log_forward_by_id_summary',
        tags: [Tags::Emails->value]
    )]
    #[IdParameter(type: ElementTypes::TYPE_EMAIL)]
    #[ForwardEmailRequestBody]
    #[SuccessResponse(
        description: 'email_log_forward_by_id_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function forwardEmail(
        int $id,
        #[MapRequestPayload] EmailAddressParameter $emailAddressParameter
    ): Response {
        $this->emailSendService->forwardEmail($id, $emailAddressParameter);

        return new Response();
    }
}
