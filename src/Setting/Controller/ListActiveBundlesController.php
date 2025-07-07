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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Attribute\Content\ActiveBundlesJson;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\BundlesServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListActiveBundlesController extends AbstractApiController
{
    private const string ROUTE = '/settings/active-bundles';

    public function __construct(
        private readonly BundlesServiceInterface $bundlesService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_settings_active_bundles', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'active_bundles_get',
        description: 'active_bundles_get_description',
        summary: 'active_bundles_get_summary',
        tags: [Tags::Settings->name]
    )]
    #[SuccessResponse(
        description: 'active_bundles_get_success_response',
        content: new ActiveBundlesJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getActiveBundles(): JsonResponse
    {
        return $this->jsonResponse(['bundles' => $this->bundlesService->getActiveBundles()]);
    }
}
