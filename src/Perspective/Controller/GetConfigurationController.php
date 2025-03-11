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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PerspectivePermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetConfigurationController extends AbstractApiController
{
    private const string ROUTE = '/perspectives/configuration/{perspectiveId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly PerspectiveServiceInterface $perspectiveService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_get_perspectives_config',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(PerspectivePermissions::VIEW_PERMISSION)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'perspective_get_config_by_id',
        description: 'perspective_get_config_by_id_description',
        summary: 'perspective_get_config_by_id_summary',
        tags: [Tags::Perspectives->value]
    )]
    #[StringParameter('perspectiveId', Perspectives::DEFAULT_ID->value, 'Get perspective by matching Id')]
    #[SuccessResponse(
        description: 'perspective_get_config_by_id_success_response',
        content: new JsonContent(ref: PerspectiveConfigDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getConfig(string $perspectiveId): JsonResponse
    {
        return $this->jsonResponse($this->perspectiveService->getConfigData($perspectiveId));
    }
}
