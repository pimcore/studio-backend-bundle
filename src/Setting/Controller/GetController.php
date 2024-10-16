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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    private const ROUTE = '/settings';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SettingsServiceInterface $settingsService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_settings', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'system_settings_get',
        description: 'system_settings_get_description',
        summary: 'system_settings_get_summary',
        tags: [Tags::Settings->name]
    )]
    #[SuccessResponse(
        description: 'system_settings_get_success_response',
        content: new JsonContent(type: 'object', additionalProperties: true)
    )]
    #[DefaultResponses]
    public function getSystemSettings(): JsonResponse
    {
        return $this->jsonResponse($this->settingsService->getSettings());
    }
}
