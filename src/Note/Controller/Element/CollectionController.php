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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Controller\Element;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteElement;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\NoteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\BadRequestResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly NoteServiceInterface $noteService
    )
    {
        parent::__construct($serializer);
    }



    #[Route('/notes/{elementType}/{id}', name: 'pimcore_studio_api_get_element_notes', methods: ['GET'])]
    #[Get(
        path: self::API_PATH . '/notes/{elementType}/{id}',
        operationId: 'getNotesForElementByTypeAndId',
        summary: 'Get notes for an element',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Notes->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[PageParameter]
    #[PageSizeParameter(50)]
    #[FilterParameter('notes')]
    #[BadRequestResponse]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getNotes(
        string $elementType,
        int $id,
        #[MapQueryString] NoteParameters $parameters = new NoteParameters()
    ): JsonResponse
    {
        $collection = $this->noteService->listNotes(new NoteElement($elementType, $id), $parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}