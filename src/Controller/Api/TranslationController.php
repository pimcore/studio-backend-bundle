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

use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Dto\Translation;
use Pimcore\Bundle\StudioApiBundle\Dto\Unauthorized;
use Pimcore\Bundle\StudioApiBundle\Service\TranslatorServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class TranslationController extends AbstractApiController
{
    private const PATH = '/translations';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TranslatorServiceInterface $translatorService
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::PATH, name: 'pimcore_studio_api_translations', methods: ['POST'])]
    #[IsGranted(self::VOTER_PUBLIC_STUDIO_API, 'translation')]
    #[POST(
        path: self::API_PATH . self::PATH,
        description: 'Get translations for given keys and locale',
        summary: 'Get translations',
        security: [
            [
                'auth_token' => [],
            ],
        ],
        tags: ['Translation']
    )]
    #[RequestBody(
        required: true,
        content:[
            new MediaType(
                mediaType: 'application/json',
                schema: new Schema(ref: Translation::class, type: 'object')
            ),
        ]
    )]
    #[Response(
        response: 200,
        description: 'Key value pairs for given keys and locale',
        content:[
            new MediaType(
                mediaType: 'application/json',
                schema: new Schema(ref: Translation::class, type: 'object')
            ),
        ]
    )]
    #[Response(
        response: 401,
        description: 'Unauthorized',
        content:[
            new MediaType(
                mediaType: 'application/json',
                schema: new Schema(ref: Unauthorized::class, type: 'object')
            ),
        ]
    )]
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
