<?php

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

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api\V1;

use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Dto\Translation;
use Pimcore\Bundle\StudioApiBundle\Service\TranslatorServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class TranslationController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly TranslatorServiceInterface $translatorService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/v1/translations', name: 'pimcore_studio_api_v1_translations', methods: ['POST'])]
    #[IsGranted('PUBLIC_API_PLATFORM', 'translation')]
    public function getTranslations(
        #[MapRequestPayload] Translation $translation,
    ): JsonResponse {

        if(empty($translation->getKeys())) {
            return $this->jsonLd($this->translatorService->getAllTranslations($translation->getLocale()));
        }

        return $this->jsonLd(
            $this->translatorService->getTranslationsForKeys(
                $translation->getLocale(),
                $translation->getKeys()
            )
        );
    }
}
