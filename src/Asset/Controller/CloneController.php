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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CloneController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly CloneServiceInterface $cloneService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route('/assets/{id}/clone/{parentId}', name: 'pimcore_studio_api_assets_clone', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::API_PATH . '/assets/{id}/clone/{parentId}',
        operationId: 'cloneElement',
        summary: 'Clone a specific asset.',
        tags: [Tags::Assets->value]
    )]
    #[SuccessResponse(
        description: 'Successfully copied asset',
    )]
    #[CreatedResponse(
        description: 'Successfully copied parent asset and created jobRun for copying child assets',
        content: new IdJson('ID of created jobRun')
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET)]
    #[IdParameter(type: ElementTypes::TYPE_ASSET, name: 'parentId')]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function cloneElement(
        int $id,
        int $parentId
    ): Response {
        $status = HttpResponseCodes::SUCCESS->value;
        $data = null;
        $jobRunId = $this->cloneService->cloneAssetRecursively($id, $parentId);

        if ($jobRunId) {
            $status = HttpResponseCodes::CREATED->value;

            return $this->jsonResponse(['id' => $jobRunId], $status);
        }

        return new Response($data, $status);
    }
}
