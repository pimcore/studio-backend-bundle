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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Controller\Element;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Service\ScheduleServiceInterface;
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
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly ScheduleServiceInterface $scheduleService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/schedules/{elementType}/{id}', name: 'pimcore_studio_api_get_element_schedules', methods: ['GET'])]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . '/schedules/{elementType}/{id}',
        operationId: 'schedule_get_collection_for_element_by_type_and_id',
        description: 'schedule_get_collection_for_element_by_type_and_id_description',
        summary: 'schedule_get_collection_for_element_by_type_and_id_summary',
        tags: [Tags::Schedule->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[SuccessResponse(
        description: 'schedule_get_collection_for_element_by_type_and_id_success_response',
        content: new ItemsJson(Schedule::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getSchedules(
        string $elementType,
        int $id
    ): JsonResponse {
        $parameters = new ElementParameters($elementType, $id);

        return $this->jsonResponse(
            [
                'items' => $this->scheduleService->listSchedules(
                    $parameters->getType(),
                    $parameters->getId()
                ),
            ]
        );
    }
}
