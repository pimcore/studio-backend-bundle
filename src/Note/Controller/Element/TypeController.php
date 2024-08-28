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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Note\Attribute\Response\Content\NoteTypesJson;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\NoteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TypeController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly NoteServiceInterface $noteService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route('/notes/type/{elementType}', name: 'pimcore_studio_api_get_element_note_types', methods: ['GET'])]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[IsGranted(UserPermissions::NOTES_EVENTS->value)]
    #[Get(
        path: self::API_PATH . '/notes/type/{elementType}',
        operationId: 'note_element_get_type_collection',
        description: 'note_element_get_type_collection_description',
        summary: 'note_element_get_type_collection_summary',
        tags: [Tags::Notes->name]
    )]
    #[ElementTypeParameter]
    #[SuccessResponse(
        description: 'note_element_get_type_collection_success_response',
        content: new NoteTypesJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getNoteTypes(
        string $elementType
    ): JsonResponse {

        return $this->jsonResponse($this->noteService->listNoteTypes($elementType));
    }
}