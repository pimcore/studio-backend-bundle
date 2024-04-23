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

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioApiBundle\Attributes\Request\TranslationRequestBody;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioApiBundle\Config\Tags;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Translation;
use Pimcore\Bundle\StudioApiBundle\Service\TranslatorServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TranslationController extends AbstractApiController
{
    private const ROUTE = '/translations';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TranslatorServiceInterface $translatorService
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations', methods: ['POST'])]
    //#[IsGranted(self::VOTER_PUBLIC_STUDIO_API, 'translation')]
    #[POST(
        path: self::API_PATH . self::ROUTE,
        operationId: 'getTranslations',
        description: 'Get translations for given keys and locale',
        summary: 'Get translations',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Translation->name]
    )]
    #[TranslationRequestBody]
    #[SuccessResponse(
        description: 'Key value pairs for given keys and locale',
        content: new JsonContent(ref: Translation::class)
    )]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getTranslations(
        #[MapRequestPayload] Translation $translation,
    ): JsonResponse {

        if(empty($translation->getKeys())) {
            return $this->jsonResponse($this->translatorService->getAllTranslations($translation->getLocale()));
        }

        return $this->jsonResponse(
            $this->translatorService->getTranslationsForKeys(
                $translation->getLocale(),
                $translation->getKeys()
            )
        );
    }
}
