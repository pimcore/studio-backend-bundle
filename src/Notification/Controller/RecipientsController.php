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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Notification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Recipient;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\NotificationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
final class RecipientsController extends AbstractApiController
{
    use PaginatedResponseTrait;
    public function __construct(
        SerializerInterface $serializer,
        private readonly UserServiceInterface $userService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws UserNotFoundException
     */
    #[Route('/notifications/recipients', name: 'pimcore_studio_api_notification_get_recipients', methods: ['GET'])]
    #[IsGranted(UserPermissions::NOTIFICATIONS_SEND->value)]
    #[Get(
        path: self::PREFIX . '/notifications/recipients',
        operationId: 'notification_get_recipients',
        description: 'notification_get_recipients_description',
        summary: 'notification_get_recipients_summary',
        tags: [Tags::Notifications->value]
    )]
    #[SuccessResponse(
        description: 'notification_get_recipients_success_response',
        content: new CollectionJson(new GenericCollection(Recipient::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getRecipients(): JsonResponse
    {
        $items = $this->userService->getRecipientsForCurrentUser();
        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items),
        );
    }
}
