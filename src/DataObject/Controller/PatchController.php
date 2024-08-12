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

use OpenApi\Attributes\Patch;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Attributes\Request\PatchDataObjectRequestBody;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\DataParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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
final class PatchController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PatchServiceInterface $patchService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException|ElementSavingFailedException
     * @throws NotFoundException|UserNotFoundException|InvalidArgumentException
     */
    #[Route('/data-objects', name: 'pimcore_studio_api_patch_data_object', methods: ['PATCH'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Patch(
        path: self::API_PATH . '/data-objects',
        operationId: 'data_object_patch_by_id',
        description: 'data_object_patch_by_id_description',
        summary: 'data_object_patch_by_id_summary',
        tags: [Tags::DataObjects->name]
    )]
    #[PatchDataObjectRequestBody]
    #[SuccessResponse(
        description: 'data_object_patch_by_id_success_response',
    )]
    #[CreatedResponse(
        description: 'data_object_patch_by_id_created_response',
        content: new IdJson('ID of created jobRun')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function dataObjectPatchById(#[MapRequestPayload] DataParameter $parameter): Response
    {
        $jobRunId = $this->patchService->patch(
            ElementTypes::TYPE_OBJECT,
            $parameter->getData(),
            $this->securityService->getCurrentUser()
        );

        if ($jobRunId) {
            return $this->jsonResponse(['id' => $jobRunId], HttpResponseCodes::CREATED->value);
        }

        return new Response();
    }
}
