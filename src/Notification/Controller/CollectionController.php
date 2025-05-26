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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\NotificationListItem;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\NotificationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly FilterMapperServiceInterface $filterMapper,
        private readonly NotificationServiceInterface $notificationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws UserNotFoundException
     */
    #[Route('/notifications', name: 'pimcore_studio_api_notifications_list', methods: ['POST'])]
    #[IsGranted(UserPermissions::NOTIFICATIONS->value)]
    #[Post(
        path: self::PREFIX . '/notifications',
        operationId: 'notification_get_collection',
        description: 'notification_get_collection_description',
        summary: 'notification_get_collection_summary',
        tags: [Tags::Notifications->value]
    )]
    #[CollectionRequestBody(
        columnFiltersExample: '[' .
            '{"key":"creationDate", "type":"date", "filterValue":{"operator": "on", "value": "08/20/2024"}},' .
            '{"key":"title", "type":"like", "filterValue": "notification"},' .
            '{"key":"type", "type":"equals", "filterValue": "info"}'
            . ']',
        sortFilterExample: '{"key":"creationDate", "direction":"DESC"}'
    )]
    #[SuccessResponse(
        description: 'notification_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(NotificationListItem::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getNotificationCollection(
        #[MapRequestPayload] CollectionFilterParameter $parameters
    ): JsonResponse {
        $filterParameters = new FilterParameter();
        if ($parameters->getFilters()) {
            $filterParameters = $this->filterMapper->map($parameters);
        }

        $collection = $this->notificationService->listNotifications($filterParameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
