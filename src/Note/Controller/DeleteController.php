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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\NoteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly NoteServiceInterface $noteService
    )
    {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementNotFoundException
     */
    #[Route('/notes/{id}', name: 'pimcore_studio_api_delete_note', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::NOTES_EVENTS->value)]
    #[Delete(
        path: self::API_PATH . '/notes/{id}',
        operationId: 'deleteNote',
        summary: 'Deleting note by id',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Notes->name]
    )]
    #[IdParameter]
    #[SuccessResponse(
        description: 'Id of the note that got deleted',
        content: new IdJson('ID of deleted note')
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED
    ])]
    public function deleteNote(int $id): JsonResponse
    {
        $this->noteService->deleteNote($id);
        return $this->jsonResponse(['id' => $id]);
    }
}
