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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscriptionCollection;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\SubscriptionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetSubscriptionsController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * The literal path is safe beside /notifications/{id} only while that route constrains its
     * id to digits.
     *
     * @throws DatabaseException
     */
    #[Route(
        '/notifications/subscriptions',
        name: 'pimcore_studio_api_notification_get_subscriptions',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::NOTIFICATIONS->value)]
    #[Get(
        path: self::PREFIX . '/notifications/subscriptions',
        operationId: 'notification_get_subscriptions',
        description: 'notification_get_subscriptions_description',
        summary: 'notification_get_subscriptions_summary',
        tags: [Tags::Notifications->value]
    )]
    #[SuccessResponse(
        description: 'notification_get_subscriptions_success_response',
        content: new JsonContent(ref: SubscriptionCollection::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getSubscriptions(): JsonResponse
    {
        return $this->jsonResponse(
            $this->subscriptionService->getSubscriptions($this->securityService->getCurrentUser())
        );
    }
}
