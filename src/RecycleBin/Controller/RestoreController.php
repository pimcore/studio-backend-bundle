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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Controller;

use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ItemsParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ScalarItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class RestoreController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/recycle-bin/restore';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RecycleBinServiceInterface $recycleBinService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_recycle_bin_restore', methods: ['POST'])]
    #[IsGranted(UserPermissions::RECYCLE_BIN->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'recycle_bin_restore_items',
        description: 'recycle_bin_restore_items_description',
        summary: 'recycle_bin_restore_items_summary',
        tags: [Tags::RecycleBin->value]
    )]
    #[RequestBody(
        content: new ScalarItemsJson('integer', 'items'),
    )]
    #[SuccessResponse(description: 'recycle_bin_restore_items_success_response')]
    #[CreatedResponse(
        description: 'recycle_bin_restore_items_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function restoreRecycleBinItems(
        #[MapRequestPayload] ItemsParameter $parameters
    ): Response {
        $jobRunId = $this->recycleBinService->restore($parameters);

        if ($jobRunId) {

            return $this->jsonResponse(['jobRunId' => $jobRunId], HttpResponseCodes::CREATED->value);
        }

        return new Response();
    }
}
