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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Service;

use InvalidArgumentException;
use Locale;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CreateTranslation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\UpdateTranslation;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PublicTranslations;
use Pimcore\Translation\Translator;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final readonly class TranslatorService implements TranslatorServiceInterface
{
    private const string API_DOCS_DOMAIN = 'studio_api_docs';

    private TranslatorBagInterface $translatorBag;

    public function __construct(
        private TranslatorInterface $translator,
        private TranslationRepositoryInterface $translationRepository,
        private SecurityServiceInterface $securityService,
    ) {
        $this->translatorBag = $this->getTranslatorBag();
    }

    public function createTranslations(CreateTranslation $translation): void
    {
        $this->translationRepository->createTranslations($translation->getTranslationData());
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslations(UpdateTranslation $translation): void
    {
        $this->translationRepository->updateTranslations($translation->getTranslationData(), $translation->getLocale());
    }

    /**
     * @throws InvalidLocaleException
     */
    public function getAllTranslations(string $locale, bool $useFallback): Translation
    {
        try {
            if ($this->translatorBag instanceof Translator) {
                $this->translatorBag->lazyInitialize(self::DOMAIN, $locale);
            }

            if (!$this->securityService->isLoggedIn()) {
                return $this->getTranslationsForKeys($locale, PublicTranslations::PUBLIC_KEYS);
            }

            $catalogue = $this->translatorBag->getCatalogue($locale)->all(self::DOMAIN);

        } catch (InvalidArgumentException) {
            throw new InvalidLocaleException($locale);
        }

        if ($useFallback) {
            $catalogue = $this->applyFallback($locale, $catalogue);
        }

        return new Translation(
            $locale,
            $catalogue,
            $useFallback
        );
    }

    /**
     * @throws InvalidLocaleException
     */
    public function getTranslationsForKeys(string $locale, array $keys): Translation
    {
        $translations = [];

        foreach ($keys as $key) {
            $translations[$key] = $this->translate($key);
        }

        return new Translation($locale, $translations);
    }

    public function deleteTranslationByKey(string $key): void
    {
        $this->translationRepository->deleteTranslation($key);
    }

    public function translate(string $message, array $params = []): string
    {
        return $this->translator->trans($message, $params, self::DOMAIN);
    }

    public function translateApiDocs(string $message, string $locale = 'en'): string
    {
        return $this->translator->trans($message, [], self::API_DOCS_DOMAIN, $locale);
    }

    private function getTranslatorBag(): TranslatorBagInterface
    {
        if (!$this->translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException('Translator must implement TranslatorBagInterface');
        }

        return $this->translator;
    }

    private function applyFallback(string $locale, array $translations): array
    {
        $fallbackLanguages = $this->getFallbackLocals($locale);

        foreach ($fallbackLanguages as $fallbackLanguage) {
            if ($this->translatorBag instanceof Translator) {
                $this->translatorBag->lazyInitialize(self::DOMAIN, $locale);
            }

            $catalogue = $this->translatorBag->getCatalogue($fallbackLanguage)->all(self::DOMAIN);
            foreach ($catalogue as $key => $value) {
                if (empty($translations[$key])) {
                    $translations[$key] = $value;
                }
            }
        }

        return $translations;
    }

    private function getFallbackLocals(string $local): array
    {
        $fallbackLanguages  = [];
        if (null !== Locale::getRegion($local)) {
            $fallbackLanguages[] = Locale::getPrimaryLanguage($local);
        }

        if ($local != 'en') {
            $fallbackLanguages[] = 'en';
        }

        return $fallbackLanguages;
    }
}
