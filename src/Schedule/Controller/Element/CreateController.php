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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotAuthorizedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Service\ScheduleServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
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
        path: self::API_PATH . '/schedules/{elementType}/{id}',
        operationId: 'createSchedule',
        summary: 'Create schedule for element',
        tags: [Tags::Schedule->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[SuccessResponse(
        description: 'Created schedule',
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
