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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Controller\RobotsTxt;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RobotsTxtConfig;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service\RobotsTxtServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    private const string ROUTE = '/bundle/seo/robots-txt';

    public function __construct(
        private readonly RobotsTxtServiceInterface $robotsTxtService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_seo_robots_txt_get', methods: ['GET'])]
    #[IsGranted(UserPermissions::ROBOTS_TXT->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_seo_robots_txt_get',
        description: 'bundle_seo_robots_txt_get_description',
        summary: 'bundle_seo_robots_txt_get_summary',
        tags: [Tags::BundleSeo->value]
    )]
    #[SuccessResponse(
        description: 'bundle_seo_robots_txt_get_success_response',
        content: new JsonContent(ref: RobotsTxtConfig::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getRobotsTxtConfig(): JsonResponse
    {
        return $this->jsonResponse($this->robotsTxtService->getRobotsTxtConfig());
    }
}
