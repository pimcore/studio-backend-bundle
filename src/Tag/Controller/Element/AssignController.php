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
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\Attributes\Request\ElementTagRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\TagElement;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\ElementTag;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AssignController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly TagServiceInterface $tagService,
    )
    {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|ElementNotFoundException
     */
    #[Route('/tags/{elementType}/{id}', name: 'pimcore_studio_api_assign_element_tag', methods: ['POST'])]
    //#[IsGranted(UserPermissions::TAGS_ASSIGNMENT->value)]
    #[Post(
        path: self::API_PATH . '/tags/{elementType}/{id}',
        operationId: 'assignTagForElement',
        summary: 'Assign tag for element',
        security: self::SECURITY_SCHEME,
        tags: [Tags::TagsForElement->value]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[ElementTagRequestBody]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED
    ])]
    public function assignTag(
        string $elementType,
        int $id,
        #[MapRequestPayload] ElementTag $assignTag
    ): JsonResponse
    {
        $this->tagService->assignTagToElement(new TagElement($elementType, $id), $assignTag->getTagId());
        return $this->jsonResponse(['id' => $id]);
    }
}
