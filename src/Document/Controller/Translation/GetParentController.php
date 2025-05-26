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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Translation\TranslationParent;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\TranslationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetParentController extends AbstractApiController
{
    private const string ROUTE = '/documents/translations/{id}/get-parent/{language}';

    public function __construct(
        private readonly TranslationServiceInterface $translationService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|ForbiddenException|InvalidArgumentException
     * @throws NotFoundException|UserNotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_documents_translations_get_parent',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_get_translation_parent_by_language',
        description: 'document_get_translation_parent_by_language_description',
        summary: 'document_get_translation_parent_by_language_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_get_translation_parent_by_language_success_response',
        content: new JsonContent(ref: TranslationParent::class)
    )]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT)]
    #[StringParameter(name: 'language', example: 'en', description: 'Language code for the translation parent')]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function addDocumentTranslation(int $id, string $language): JsonResponse
    {

        return $this->jsonResponse($this->translationService->getTranslationParent($id, $language));
    }
}
