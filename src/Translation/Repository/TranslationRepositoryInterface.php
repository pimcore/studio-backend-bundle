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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\TranslationData;
use Pimcore\Model\Translation;

/**
 * @internal
 */
interface TranslationRepositoryInterface
{
    /**
     * @throws InvalidLocaleException
     *
     * @return array<Translation>
     */
    public function getAllTranslations(string $locale): array;

    /**
     * @param array<TranslationData> $translationData
     *
     * @throws InvalidLocaleException
     */
    public function createTranslations(array $translationData, string $locale): void;

    public function deleteTranslation(string $key): void;
}
