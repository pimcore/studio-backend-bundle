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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\Folder;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\Folder\ClassDefinitionFolderItem;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassDefinitionTreeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly ClassDefinitionTreeServiceInterface $classDefinitionTreeService,

    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        '/class/folder/{folderId}',
        name: 'pimcore_studio_api_classes_folder_collection',
        requirements: ['folderId' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/class/folder/{folderId}',
        operationId: 'class_definition_folder_collection',
        description: 'class_definition_folder_collection_description',
        summary: 'class_definition_folder_collection_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[SuccessResponse(
        description: 'class_definition_folder_collection_success_response',
        content: new CollectionJson(new GenericCollection(ClassDefinitionFolderItem::class))
    )]
    #[IdParameter(type: ElementTypes::TYPE_DATA_OBJECT, name: 'folderId')]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getFolderClassList(
        int $folderId
    ): JsonResponse {
        $collection = $this->classDefinitionTreeService->getChildrenClassDefinitionIds($folderId);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection,
            count($collection)
        );
    }
}
