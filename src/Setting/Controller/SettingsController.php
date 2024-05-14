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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Request\SettingsRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\NotFoundResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Property\KeyValueArray;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SettingsRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SettingsController extends AbstractApiController
{
    private const ROUTE = '/settings';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SettingsProviderInterface $settingsProvider
    )
    {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_settings', methods: ['POST'])]

    #[POST(
        path: self::API_PATH . self::ROUTE,
        operationId: 'getSettings',
        description: 'Get settings from backend',
        summary: 'Get settings from backend based on parameter',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Settings->name]
    )]
    #[SettingsRequestBody]
    #[SuccessResponse(
        description: 'Key value pairs for given keys',
        content: new JsonContent(
            properties: [
                new KeyValueArray()
            ]
        )
    )]
    #[NotFoundResponse]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getSettings(#[MapRequestPayload] SettingsRequest $settingsRequest): JsonResponse
    {
        return $this->jsonResponse(['parameters' => $this->settingsProvider->getParameters($settingsRequest)]);
    }
}