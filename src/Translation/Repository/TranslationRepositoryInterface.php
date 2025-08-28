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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\UpdateTranslation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Model\Translation;
use Pimcore\Model\Translation\Listing;

/**
 * @internal
 */
interface TranslationRepositoryInterface
{
    /**
     * @throws ElementExistsException
     */
    public function createTranslations(bool $throwError, array $translationData): void;

    public function getTranslationList(string $domain = TranslatorServiceInterface::DOMAIN): Listing;

    /**
     * @throws InvalidLocaleException|NotFoundException
     */
    public function updateTranslations(string $domain, UpdateTranslation $updateData): void;

    public function deleteTranslation(string $key, string $domain): void;

    public function createDummyTranslation(string $domain, array $allowedLanguages): Translation;

    public function joinLanguageColumns(
        Listing $listing,
        array $languages,
        string $domain
    ): Listing;

    public function addSearchCondition(Listing $listing, string $searchTerm): Listing;

    /**
     * @return array<int, string>
     */
    public function getTranslationKeysWithTextFilter(
        string $filterTerm,
        string $language,
        string $domain = TranslatorServiceInterface::DOMAIN
    ): array;
}
