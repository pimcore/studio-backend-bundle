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
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CreateTranslation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\UpdateTranslation;

/**
 * @internal
 */
interface TranslatorServiceInterface
{
    public const string DOMAIN = 'studio';

    public function createTranslations(CreateTranslation $translation): void;

    /**
     * @throws InvalidLocaleException
     */
    public function updateTranslations(UpdateTranslation $translation): void;

    /**
     * @throws InvalidLocaleException
     */
    public function getAllTranslations(string $locale, bool $useFallback): Translation;

    /**
     * @throws InvalidLocaleException
     */
    public function getTranslationsForKeys(string $locale, array $keys): Translation;

    public function deleteTranslationByKey(string $key): void;

    public function translate(string $message, array $params = []): string;

    public function translateApiDocs(string $message, string $locale = 'en'): string;
}
