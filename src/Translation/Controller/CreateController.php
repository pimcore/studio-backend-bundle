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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Request\TranslationRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CreateTranslation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CreateController extends AbstractApiController
{
    private const string ROUTE = '/translations/create';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TranslatorServiceInterface $translatorService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|ElementExistsException|UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations_create', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'translation_create',
        description: 'translation_create_description',
        summary: 'translation_create_summary',
        tags: [Tags::Translation->name]
    )]
    #[TranslationRequestBody(CreateTranslation::class)]
    #[SuccessResponse(
        description: 'translation_create_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::CONFLICT,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createTranslations(
        #[MapRequestPayload] CreateTranslation $parameter
    ): Response {
        $this->translatorService->createTranslations($parameter);

        return new Response();
    }
}
