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

namespace Pimcore\Bundle\StudioBackendBundle\Cache\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Cache\MappedParameter\ClearCacheParameters;
use Pimcore\Bundle\StudioBackendBundle\Cache\Service\CacheServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\BoolParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\PermissionsToCheck;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ClearCacheController extends AbstractApiController
{
    private const string ROUTE = '/cache';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CacheServiceInterface $cacheService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_cache_clear', methods: ['DELETE'])]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'cache_clear',
        description: 'cache_clear_description',
        summary: 'cache_clear_summary',
        tags: [Tags::Cache->value],
    )]
    #[BoolParameter('onlyPimcoreCache', 'cache_clear_only_pimcore_cache_description', false, false)]
    #[BoolParameter('onlySymfonyCache', 'cache_clear_only_symfony_cache_description', false, false)]
    #[SuccessResponse(description: 'cache_clear_success_response')]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function clearCache(
        #[MapQueryString] ClearCacheParameters $parameters = new ClearCacheParameters(),
    ): Response {
        $this->denyAccessUnlessGranted(
            'HasOneOf',
            new PermissionsToCheck([
                UserPermissions::CLEAR_CACHE->value,
                UserPermissions::SYSTEM_SETTINGS->value,
            ]),
        );

        $this->cacheService->clearCache($parameters);

        return new Response();
    }
}
