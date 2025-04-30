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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Controller\Element;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotAuthorizedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;
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
final class CreateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ScheduleServiceInterface $scheduleService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotAuthorizedException|NotFoundException
     */
    #[Route('/schedules/{elementType}/{id}', name: 'pimcore_studio_api_create_schedule', methods: ['POST'])]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[POST(
        path: self::PREFIX . '/schedules/{elementType}/{id}',
        operationId: 'schedule_create_for_element_by_type_and_id',
        description: 'schedule_create_for_element_by_type_and_id_description',
        summary: 'schedule_create_for_element_by_type_and_id_summary',
        tags: [Tags::Schedule->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter]
    #[SuccessResponse(
        description: 'schedule_create_for_element_by_type_and_id_success_response',
        content: new JsonContent(ref: Schedule::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createSchedule(string $elementType, int $id): JsonResponse
    {
        $parameters = new ElementParameters($elementType, $id);

        return $this->jsonResponse(
            $this->scheduleService->createSchedule(
                $parameters->getType(),
                $parameters->getId()
            )
        );
    }
}
