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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Attribute\Request\SavePerspectiveRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SavePerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/perspectives/configuration/{perspectiveId}';

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
        name: 'pimcore_studio_api_update_perspectives_config',
        methods: ['PUT']
    )]
    #[IsGranted(UserPermissions::PERSPECTIVE_EDITOR->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'perspective_update_config_by_id',
        description: 'perspective_update_config_by_id_description',
        summary: 'perspective_update_config_by_id_summary',
        tags: [Tags::Perspectives->name]
    )]
    #[StringParameter('perspectiveId', 'd061699e_da42_4075_b504_c2c93c687819', 'Update perspective by matching Id')]
    #[SavePerspectiveRequestBody]
    #[SuccessResponse(
        description: 'perspective_update_config_by_id_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updatePerspectiveConfig(
        string $perspectiveId,
        #[MapRequestPayload] SavePerspectiveConfig $perspectiveConfig
    ): Response {
        $this->perspectiveService->updateConfig($perspectiveId, $perspectiveConfig);

        return new Response();
    }
}
