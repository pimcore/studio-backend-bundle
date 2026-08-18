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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Attribute\Request\UpdateSubscriptionsRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscriptionCollection;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\UpdateSubscriptionsParameters;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\SubscriptionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateSubscriptionsController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * Bulk on purpose: the preferences screen saves every row at once, so one save action is
     * one request.
     *
     * @throws DatabaseException
     * @throws InvalidArgumentException
     */
    #[Route(
        '/notifications/subscriptions',
        name: 'pimcore_studio_api_notification_update_subscriptions',
        methods: ['PUT']
    )]
    #[IsGranted(UserPermissions::NOTIFICATIONS->value)]
    #[Put(
        path: self::PREFIX . '/notifications/subscriptions',
        operationId: 'notification_update_subscriptions',
        description: 'notification_update_subscriptions_description',
        summary: 'notification_update_subscriptions_summary',
        tags: [Tags::Notifications->value]
    )]
    #[UpdateSubscriptionsRequestBody]
    #[SuccessResponse(
        description: 'notification_update_subscriptions_success_response',
        content: new JsonContent(ref: SubscriptionCollection::class)
    )]
    // Both rejections — an unknown type id and unsubscribing a locked type — are
    // InvalidArgumentException, which this bundle maps to 422, not 400.
    #[DefaultResponses([
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateSubscriptions(
        #[MapRequestPayload] UpdateSubscriptionsParameters $parameters
    ): JsonResponse {
        return $this->jsonResponse(
            $this->subscriptionService->updateSubscriptions(
                $this->securityService->getCurrentUser(),
                $parameters
            )
        );
    }
}
