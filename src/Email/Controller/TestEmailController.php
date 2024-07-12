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
use Pimcore\Bundle\StudioBackendBundle\Email\Attributes\Request\TestEmailRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\TestEmailRequest;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\EmailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TestEmailController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly EmailServiceInterface $emailService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route('/emails/test', name: 'pimcore_studio_api_emails_test', methods: ['POST'])]
    #[IsGranted(UserPermissions::EMAILS->value)]
    #[Post(
        path: self::API_PATH . '/emails/test',
        operationId: 'sendTestEmail',
        summary: 'Send a test email.',
        tags: [Tags::Emails->value]
    )]
    #[TestEmailRequestBody]
    #[SuccessResponse(
        description: 'Mail was successfully sent',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function sendTestEmail(
        #[MapRequestPayload] TestEmailRequest $parameters
    ): Response {
        $this->emailService->sendTestEmail(
            $parameters,
            $this->securityService->getCurrentUser()
        );

        return new Response();
    }
}