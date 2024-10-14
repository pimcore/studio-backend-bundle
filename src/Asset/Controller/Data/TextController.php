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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Data;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\Data\TextServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\MaxFileSizeExceededException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\DataJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TextController extends AbstractApiController
{
    use ElementProviderTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly TextServiceInterface $dataService

    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|InvalidElementTypeException|MaxFileSizeExceededException
     */
    #[Route('/assets/{id}/text', name: 'pimcore_studio_api_get_asset_data_text', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/{id}/text',
        operationId: 'asset_get_text_data_by_id',
        description: 'asset_get_text_data_by_id_description',
        summary: 'asset_get_text_data_by_id_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET)]
    #[SuccessResponse(
        description: 'asset_get_text_data_by_id_success_response',
        content: new DataJson('UTF 8 encoded text data')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getTextData(int $id): JsonResponse
    {
        return $this->jsonResponse(['data' => $this->dataService->getUTF8EncodedData($id)]);
    }
}
