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
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Response\Content\CustomSettingsJson;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\Data\CustomSettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
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
final class CustomSettingsController extends AbstractApiController
{
    use ElementProviderTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomSettingsServiceInterface $customSettingsService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route('/assets/{id}/custom-settings', name: 'pimcore_studio_api_get_asset_custom_settings', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[GET(
        path: self::PREFIX . '/assets/{id}/custom-settings',
        operationId: 'asset_custom_settings_get_by_id',
        description: 'asset_custom_settings_get_by_id_description',
        summary: 'asset_custom_settings_get_by_id_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET)]
    #[SuccessResponse(
        description: 'asset_custom_settings_get_by_id_success_response',
        content: new CustomSettingsJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getAssetCustomSettingsById(int $id): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->customSettingsService->getCustomSettings($id)]);
    }
}
