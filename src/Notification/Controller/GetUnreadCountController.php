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
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\UnreadCount;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\NotificationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetUnreadCountController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly NotificationServiceInterface $notificationService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/notifications/unread-count',
        name: 'pimcore_studio_api_notification_get_unread_count',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::NOTIFICATIONS->value)]
    #[Get(
        path: self::PREFIX . '/notifications/unread-count',
        operationId: 'notification_get_unread_count',
        description: 'notification_get_unread_count_description',
        summary: 'notification_get_unread_count_summary',
        tags: [Tags::Notifications->value]
    )]
    #[SuccessResponse(
        description: 'notification_get_unread_count_success_response',
        content: new JsonContent(ref: UnreadCount::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getUnreadCount(): JsonResponse
    {
        return $this->jsonResponse(
            $this->notificationService->getUnreadNotificationsCount()
        );
    }
}
