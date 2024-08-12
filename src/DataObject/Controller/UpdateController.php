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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Attributes\Request\UpdateDataObjectRequestBody;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Attributes\Response\Content\OneOfDataObjectsJson;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\DataParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
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
    public function __construct(
        SerializerInterface $serializer,
        private readonly DataObjectServiceInterface $dataObjectService,
        private readonly UpdateServiceInterface $updateService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/data-objects/{id}', name: 'pimcore_studio_api_update_data_object', methods: ['PUT'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Put(
        path: self::API_PATH . '/data-objects/{id}',
        operationId: 'dataObjectUpdateById',
        description: 'data_object_update_by_id_description',
        summary: 'data_object_update_by_id_summary',
        tags: [Tags::DataObjects->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_DATA_OBJECT)]
    #[UpdateDataObjectRequestBody]
    #[SuccessResponse(
        description: 'data_object_update_by_id_success_response',
        content: new OneOfDataObjectsJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function dataObjectUpdateById(int $id, #[MapRequestPayload] DataParameter $parameter): JsonResponse
    {
        $this->updateService->update(ElementTypes::TYPE_OBJECT, $id, $parameter->getData());

        return $this->jsonResponse($this->dataObjectService->getDataObject($id));
    }
}
