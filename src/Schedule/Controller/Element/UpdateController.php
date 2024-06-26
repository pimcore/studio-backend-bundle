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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Attributes\Request\ElementScheduleRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Service\ScheduleServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly ScheduleServiceInterface $scheduleService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException
     */
    #[Route('/schedules/{elementType}/{id}', name: 'pimcore_studio_api_update_schedules', methods: ['PUT'])]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Put(
        path: self::API_PATH . '/schedules/{elementType}/{id}',
        operationId: 'updateSchedulesForElementByTypeAndId',
        summary: 'Update schedules for an element',
        tags: [Tags::Schedule->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[ElementScheduleRequestBody]
    #[SuccessResponse(
        description: 'List of schedules',
        content: new ItemsJson(Schedule::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateSchedules(
        string $elementType,
        int $id,
        #[MapRequestPayload] UpdateElementSchedules $updateElementSchedules
    ): JsonResponse {
        $parameters = new ElementParameters($elementType, $id);
        $this->scheduleService->updateSchedules(
            $parameters->getType(),
            $parameters->getId(),
            $updateElementSchedules
        );

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
