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
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final readonly class TranslatorService implements TranslatorServiceInterface
{
    public const DOMAIN = 'studio';

    private const API_DOCS_DOMAIN = 'studio_api_docs';

    private TranslatorBagInterface $translatorBag;

    public function __construct(
        private TranslatorInterface $translator
    ) {
        $this->translatorBag = $this->getTranslatorBag();
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

        return new Translation($locale, $translations);
    }

    public function translate(string $message, array $params = []): string
    {
        return $this->translator->trans($message, $params, self::DOMAIN);
    }

    public function translateApiDocs(string $message): string
    {
        return $this->translator->trans($message, [], self::API_DOCS_DOMAIN);
    }

    private function getTranslatorBag(): TranslatorBagInterface
    {
        if (!$this->translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException('Translator must implement TranslatorBagInterface');
        }

        return $this->translator;
    }
}
