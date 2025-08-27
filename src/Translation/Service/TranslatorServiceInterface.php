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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Translation\MappedParameter\UpdateParameter;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CreateTranslation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\UpdateTranslation;
use Pimcore\Model\Translation\Listing;

/**
 * @internal
 */
interface TranslatorServiceInterface
{
    public const string DOMAIN = 'studio';

    public function createTranslations(CreateTranslation $translation): void;

    /**
     *
     *
     * @throws InvalidLocaleException|NotFoundException
     */
    public function updateTranslations(string $domain, UpdateParameter $parameter): void;

    /**
     * Get all translations for a specific locale.
     *
     * @throws InvalidLocaleException
     */
    public function getAllTranslationsByLocale(string $locale, bool $useFallback): Translation;

    /**
     * @throws InvalidLocaleException
     */
    public function getTranslationsForKeys(string $locale, array $keys): Translation;

    public function deleteTranslationByKey(string $key, string $domain): void;

    public function translate(string $message, array $params = []): string;

    public function translateApiDocs(string $message, string $locale = 'en'): string;

    /**
     * Returns a list of all available domains for translations.
     */
    public function getAvailableDomains(): array;

    /**
     * Returns a list of all available locals for translations.
     */
    public function getAvailableLocales(): array;

    /**
     * Returns a list of all available translations including all languages.
     * Used for grid listing including filters and pagination.
     */
    public function listTranslations(string $domain, CollectionFilterParameter $parameter): Collection;

    public function getTranslationList(string $domain, CollectionFilterParameter $parameter): Listing;

    /**
     *
     * @throws NotFoundException
     */
    public function cleanup(string $domain): void;
}
