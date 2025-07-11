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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\TranslationData;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Model\Translation\Listing;

/**
 * @internal
 */
interface TranslationRepositoryInterface
{
    public function createTranslations(array $translationData): void;

    /**
     * @param array<TranslationData> $translationData
     *
     * @throws InvalidLocaleException
     */
    public function updateTranslations(array $translationData, string $locale): void;

    public function deleteTranslation(string $key): void;

    public function joinLanguageColumns(
        Listing $listing,
        array $languages,
        string $domain
    ): Listing;

    /**
     * @return array<int, string>
     */
    public function getTranslationKeysWithTextFilter(
        string $filterTerm,
        string $language,
        string $domain = TranslatorServiceInterface::DOMAIN
    ): array;
}
