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
use Pimcore\Bundle\StudioBackendBundle\Cache\Service\CacheServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ClearTemporaryFilesController extends AbstractApiController
{
    private const string ROUTE = '/cache/temporary-files';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CacheServiceInterface $cacheService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_cache_clear_temporary_files', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::CLEAR_TEMP_FILES->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'cache_clear_temporary_files',
        description: 'cache_clear_temporary_files_description',
        summary: 'cache_clear_temporary_files_summary',
        tags: [Tags::Cache->value],
    )]
    #[SuccessResponse(description: 'cache_clear_temporary_files_success_response')]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function clearTemporaryFiles(): Response
    {
        $this->cacheService->clearTemporaryFiles();

        return new Response();
    }
}
