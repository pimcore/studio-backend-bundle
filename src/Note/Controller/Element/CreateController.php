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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Note\Attributes\Request\CreateNoteRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteElement;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\NoteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
final class CreateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly NoteServiceInterface $noteService
    )
    {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|ElementNotFoundException
     */
    #[Route('/notes/{elementType}/{id}', name: 'pimcore_studio_api_create_element_note', methods: ['POST'])]
    #[IsGranted(UserPermissions::NOTES_EVENTS->value)]
    #[Post(
        path: self::API_PATH . '/notes/{elementType}/{id}',
        operationId: 'createNoteForElement',
        summary: 'Creating new note for element',
        security: self::SECURITY_SCHEME,
        tags: [Tags::NotesForElement->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[CreateNoteRequestBody]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED
    ])]
    public function createNote(
        string $elementType,
        int $id,
        #[MapRequestPayload] CreateNote $createNote
    ): JsonResponse
    {
        $note = $this->noteService->createNote(new NoteElement($elementType, $id), $createNote);
        return $this->jsonResponse(['id' => $note->getId()]);
    }
}
