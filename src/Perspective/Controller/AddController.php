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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Attribute\Request\AddPerspectiveRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\AddPerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AddController extends AbstractApiController
{
    private const string ROUTE = '/perspectives/configuration';

    public function __construct(
        SerializerInterface $serializer,
        private readonly PerspectiveServiceInterface $perspectiveService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_create_perspectives_config',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::PERSPECTIVE_EDITOR->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'perspective_create',
        description: 'perspective_create_description',
        summary: 'perspective_create_summary',
        tags: [Tags::Perspectives->name]
    )]
    #[AddPerspectiveRequestBody]
    #[SuccessResponse(
        description: 'perspective_create_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function addWidgetConfig(
        #[MapRequestPayload] AddPerspectiveConfig $perspectiveConfig
    ): JsonResponse {

        return $this->jsonResponse(['id' => $this->perspectiveService->addConfig($perspectiveConfig)]);
    }
}
