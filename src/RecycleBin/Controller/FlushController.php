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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class FlushController extends AbstractApiController
{
    private const string ROUTE = '/recycle-bin/flush';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RecycleBinServiceInterface $recycleBinService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_recycle_bin_flush', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::RECYCLE_BIN->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'recycle_bin_flush',
        description: 'recycle_bin_flush_description',
        summary: 'recycle_bin_flush_summary',
        tags: [Tags::RecycleBin->value]
    )]
    #[SuccessResponse(
        description: 'recycle_bin_flush_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function flushRecycleBin(): Response
    {
        $this->recycleBinService->flushRecycleBin();

        return new Response();
    }
}
