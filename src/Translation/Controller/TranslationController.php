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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Request\TranslationRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    /**
     * @throws InvalidLocaleException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_translations', methods: ['POST'])]
    #[IsGranted(self::VOTER_PUBLIC_STUDIO_API, 'translation')]
    #[POST(
        path: self::API_PATH . self::ROUTE,
        operationId: 'translation_get_collection',
        description: 'translation_get_collection_description',
        summary: 'translation_get_collection_summary',
        tags: [Tags::Translation->name]
    )]
    #[TranslationRequestBody]
    #[SuccessResponse(
        description: 'translation_get_collection_success_response',
        content: new JsonContent(ref: Translation::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getTranslations(
        #[MapRequestPayload] Translation $translation,
    ): JsonResponse {

        if (empty($translation->getKeys())) {
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
