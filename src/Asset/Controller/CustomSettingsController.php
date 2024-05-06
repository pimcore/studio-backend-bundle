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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\NotFoundResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CustomSettingsController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomSettingsHydratorInterface $hydrator,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets/{id}/custom-settings', name: 'pimcore_studio_api_get_asset_custom_settings', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[GET(
        path: self::API_PATH . '/assets/{id}/custom-settings',
        operationId: 'getAssetCustomSettingsById',
        description: 'Get custom settings of an asset by its id by path parameter',
        summary: 'Get custom settings of an asset by id',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'asset')]
    #[SuccessResponse(
        description: 'One of asset types',
        content: new JsonContent(
            properties: [
                new Property(
                    'customSettings',
                    ref: CustomSettings::class,
                    type: 'object'
                )
            ]
        )
    )]
    #[UnauthorizedResponse]
    #[NotFoundResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getAssetCustomSettingsById(int $id): JsonResponse
    {
        $asset = Asset::getById($id);
        if (!$asset instanceof Asset) {
            throw new ElementNotFoundException($id);
        }

        return $this->jsonResponse(
            $this->hydrator->hydrate($asset->getCustomSettings())
        );
    }
}
