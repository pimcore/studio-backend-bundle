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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\ScheduleAction;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Service\ScheduleServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListActionsController extends AbstractApiController
{
    private const string ROUTE = '/schedules/actions/{elementType}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ScheduleServiceInterface $scheduleService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_schedule_list_actions', methods: ['GET'], priority: 10)]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'schedule_list_actions_for_element_type',
        description: 'schedule_list_actions_for_element_type_description',
        summary: 'schedule_list_actions_for_element_type_summary',
        tags: [Tags::Schedule->value]
    )]
    #[ElementTypeParameter]
    #[SuccessResponse(
        description: 'schedule_list_actions_for_element_type_success_response',
        content: new ItemsJson(ScheduleAction::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listActions(string $elementType): JsonResponse
    {
        return $this->jsonResponse(
            ['items' => $this->scheduleService->listActions($elementType)]
        );
    }
}
