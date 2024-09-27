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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Controller\Element;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementTypeParameter as MappedElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\Attribute\Request\ElementsTagsCollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\ElementTagIdCollection;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
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
final class BatchReplaceController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly TagServiceInterface $tagService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|NotFoundException
     */
    #[Route(
        '/tags/batch/replace/{elementType}',
        name: 'pimcore_studio_api_batch_replace_elements_tags',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::TAGS_ASSIGNMENT->value)]
    #[Post(
        path: self::API_PATH . '/tags/batch/replace/{elementType}',
        operationId: 'tag_batch_replace_for_elements_by_type',
        description: 'tag_batch_replace_for_elements_by_type_description',
        summary: 'tag_batch_replace_for_elements_by_type_summary',
        tags: [Tags::TagsForElement->value]
    )]
    #[ElementTypeParameter]
    #[ElementsTagsCollectionRequestBody]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function assignTag(
        string $elementType,
        #[MapRequestPayload] ElementTagIdCollection $elementTagCollection
    ): JsonResponse {
        $this->tagService->batchReplaceTagsToElements(
            new BatchCollectionParameters(
                (new MappedElementTypeParameter($elementType))->getType(),
                $elementTagCollection->getElementIds(),
                $elementTagCollection->getTagsIds()
            )
        );

        return $this->jsonResponse(['id' => 0]);
    }
}
