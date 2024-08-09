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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Attributes\Request\CloneRequestBody;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\CloneParameters;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CloneController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CloneServiceInterface $cloneService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route('/data-objects/{id}/clone/{parentId}', name: 'pimcore_studio_api_data_objects_clone', methods: ['POST'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::API_PATH . '/data-objects/{id}/clone/{parentId}',
        operationId: 'cloneDataObject',
        description: 'clone_data_object_description',
        summary: 'clone_data_object_summary',
        tags: [Tags::DataObjects->value]
    )]
    #[SuccessResponse(
        description: 'clone_data_object_success_response',
    )]
    #[CreatedResponse(
        description: 'clone_data_object_created_response',
        content: new IdJson('ID of created jobRun')
    )]
    #[IdParameter(type: ElementTypes::TYPE_DATA_OBJECT)]
    #[IdParameter(type: ElementTypes::TYPE_DATA_OBJECT, name: 'parentId')]
    #[CloneRequestBody]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function cloneDataObject(
        int $id,
        int $parentId,
        #[MapRequestPayload] CloneParameters $parameters
    ): Response {
        $jobRunId = $this->cloneService->cloneDataObjects($id, $parentId, $parameters);
        if ($jobRunId) {

            return $this->jsonResponse(['id' => $jobRunId], HttpResponseCodes::CREATED->value);
        }

        return new Response();
    }
}
