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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

final class EditorCollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException|UserNotFoundException
     */
    #[Route(
        '/class/custom-layout/editor/collection/{objectId}',
        name: 'pimcore_studio_api_class_custom_layout_editor_collection',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/class/custom-layout/editor/collection/{objectId}',
        operationId: 'class_custom_layout_editor_collection',
        description: 'class_custom_layout_editor_collection_description',
        summary: 'class_custom_layout_editor_collection_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[IdParameter(name: 'objectId', required: true)]
    #[SuccessResponse(
        description: 'class_custom_layout_editor_collection_success_response',
        content: new CollectionJson(new GenericCollection(CustomLayoutCompact::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getEditorCustomLayouts(int $objectId): JsonResponse
    {
        $items = $this->customLayoutService->getCustomLayoutEditorCollection(
            $objectId,
            $this->securityService->getCurrentUser()
        );

        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items)
        );
    }
}
