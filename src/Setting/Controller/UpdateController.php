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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Controller;

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Attribute\Request\UpdateSettingsRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Setting\MappedParameter\UpdateSettingsParameter;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
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
        private readonly SettingsServiceInterface $settingsService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|FieldValidationFailedException
     */
    #[Route('/settings', name: 'pimcore_studio_api_update_settings', methods: ['PUT'])]
    #[IsGranted(UserPermissions::SYSTEM_SETTINGS->value)]
    #[Put(
        path: self::PREFIX . '/settings',
        operationId: 'settings_update',
        description: 'settings_update_description',
        summary: 'settings_update_summary',
        tags: [Tags::Settings->name]
    )]
    #[UpdateSettingsRequestBody]
    #[SuccessResponse(
        description: 'Updated system settings',
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function updateSettings(
        #[MapRequestPayload] UpdateSettingsParameter $updateSettings
    ): JsonResponse {
        $this->settingsService->updateSettings($updateSettings->getData());

        return $this->jsonResponse($this->settingsService->getSettings());
    }
}
