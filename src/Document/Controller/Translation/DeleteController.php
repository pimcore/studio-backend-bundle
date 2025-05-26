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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\Translation;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\TranslationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/documents/translations/{id}/delete/{translationId}';

    public function __construct(
        private readonly TranslationServiceInterface $translationService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|UserNotFoundException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_documents_translation_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_delete_translation',
        description: 'document_delete_translation_description',
        summary: 'document_delete_translation_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(description: 'document_delete_translation_success_response')]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT)]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT, name: 'translationId')]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function removeDocumentTranslation(int $id, int $translationId): Response
    {
        $this->translationService->removeDocumentTranslation($id, $translationId);

        return new Response();
    }
}
