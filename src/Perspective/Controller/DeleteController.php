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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/perspectives/configuration/{perspectiveId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly PerspectiveServiceInterface $perspectiveService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_delete_perspectives_config', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::PERSPECTIVE_EDITOR->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'perspective_delete',
        description: 'perspective_delete_description',
        summary: 'perspective_delete_summary',
        tags: [Tags::Perspectives->value]
    )]
    #[StringParameter('perspectiveId', 'd061699e_da42_4075_b504_c2c93c687819', 'Get perspective by matching Id')]
    #[SuccessResponse(
        description: 'perspective_delete_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteWidgetConfig(string $perspectiveId): Response
    {
        $this->perspectiveService->deleteConfig($perspectiveId);

        return new Response();
    }
}
