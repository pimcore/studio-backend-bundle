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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Collection;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\CollectionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetCollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private CollectionServiceInterface $collectionService;

    public function __construct(
        SerializerInterface $serializer,
        CollectionServiceInterface $collectionService,
    ) {
        parent::__construct($serializer);
        $this->collectionService = $collectionService;
    }

    #[Route('/classification-store/collections', name: 'pimcore_studio_api_classification_store_get_collections', methods: ['GET'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/classification-store/collections',
        operationId: 'classification_store_get_collections',
        description: 'classification_store_get_collections_description',
        summary: 'classification_store_get_collections_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[SuccessResponse(
        description: 'classification_store_get_collections_response',
        content: new CollectionJson(new GenericCollection(Collection::class))
    )]
    #[IdParameter(
        description: 'Classification Store',
        namePrefix: 'store',
    )]
    #[IdParameter(
        description: 'object',
        namePrefix: 'object',
        required: false
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[TextFieldParameter(
        name: 'fieldName',
        description: 'Field Name',
        required: true,
        example: 'technicalAttributes'
    )]
    public function getCollections(
        #[MapQueryString] ListClassificationStoreParameter $parameters
    ): JsonResponse {
        $collection = $this->collectionService->getCollections($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
