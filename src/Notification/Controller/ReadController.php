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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\NotificationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ReadController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly NotificationServiceInterface $notificationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException|NotFoundException|UserNotFoundException
     */
    #[Route('/notifications/{id}', name: 'pimcore_studio_api_delete_notification', methods: ['POST'])]
    #[IsGranted(UserPermissions::NOTIFICATIONS->value)]
    #[POST(
        path: self::API_PATH . '/notifications/{id}',
        operationId: 'notification_read_by_id',
        description: 'notification_read_by_id_description',
        summary: 'notification_read_by_id_summary',
        tags: [Tags::Notifications->name]
    )]
    #[IdParameter(type: 'version')]
    #[SuccessResponse(
        description: 'notification_read_by_id_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function readNotificationById(int $id): Response
    {
        $this->notificationService->markNotificationAsRead($id);

        return new Response();
    }
}
