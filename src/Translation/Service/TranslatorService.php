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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Service;

use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\UpdateTranslation;
use Pimcore\Model\Translation as TranslationModel;
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
    ) {
        $this->translatorBag = $this->getTranslatorBag();
    }

    public function updateTranslations(UpdateTranslation $translation): void
    {
        $this->translationRepository->createTranslations($translation->getTranslationData(), $translation->getLocale());
    }

    /**
     * @throws InvalidLocaleException
     */
    public function getAllTranslations(string $locale): Translation
    {
        try {
            $catalogue = $this->translatorBag->getCatalogue($locale)->all(self::DOMAIN);
        } catch (InvalidArgumentException) {
            throw new InvalidLocaleException($locale);
        }

        $catalogue = $this->addDatabaseTranslations($catalogue, $locale);

        return new Translation(
            $locale,
            $catalogue
        );
    }

    /**
     * @throws InvalidLocaleException
     */
    public function getTranslationsForKeys(string $locale, array $keys): Translation
    {
        try {
            $catalogue = $this->translatorBag->getCatalogue($locale);
        } catch (InvalidArgumentException) {
            throw new InvalidLocaleException($locale);
        }

        $translations = [];

        foreach ($keys as $key) {
            $translations[$key] = $catalogue->get($key, self::DOMAIN);
        }

        if (!empty($keys)) {
            $translations = $this->addDatabaseTranslations($translations, $locale, $keys);
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

    private function addDatabaseTranslations(array $catalogue, string $locale, array $keys = []): array
    {
        $databaseCatalogue = [];
        foreach ($this->translationRepository->getAllTranslations($locale) as $translation) {
            if(!empty($keys) && !in_array($translation->getKey(), $keys, true)) {
                continue;
            }
            $databaseCatalogue[$translation->getKey()] = $translation->getTranslation($locale);
        }

        foreach ($catalogue as $key => $translation) {
            if (!empty($databaseCatalogue[$key])) {
                $catalogue[$key] = $databaseCatalogue[$key];
            }

            unset($databaseCatalogue[$key]);
        }

        $catalogue = array_replace($databaseCatalogue, $catalogue);
        ksort($catalogue);

        return $catalogue;
    }
}
