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

use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\TranslationData;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Model\Translation;
use Pimcore\Model\Translation\Listing;
use function in_array;

/**
 * @internal
 */
final readonly class TranslationRepository implements TranslationRepositoryInterface
{
    public function __construct(
        private AdminResolverInterface $adminResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTranslations(string $locale): array
    {
        $validLanguages = $this->adminResolver->getLanguages();
        $this->validateLocale($locale, $validLanguages);

        $list = $this->getTranslationList();
        $list->setLanguages($validLanguages);
        $list->load();

        return $list->getTranslations();
    }

    /**
     * {@inheritdoc}
     */
    public function createTranslations(string $key, string $type): void
    {
        $languages = $this->adminResolver->getLanguages();

        if ($this->getTranslationByKey($key) !== null) {
            throw new ElementExistsException(
                sprintf("Translation with key '%s' already exists", $key),
            );
        }

        $t = new Translation();
        $t->setDomain(TranslatorServiceInterface::DOMAIN);
        $t->setKey($key);
        $t->setType($type);
        $t->setCreationDate(time());
        $t->setModificationDate(time());
        $this->setNewValues($t, $languages);
        $t->save();

    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslations(array $translationData, string $locale): void
    {
        $languages = $this->adminResolver->getLanguages();
        $this->validateLocale($locale, $languages);

        /** @var TranslationData $translation */
        foreach ($translationData as $translation) {
            if ($this->getTranslationByKey($translation->getKey()) === null) {
                throw new NotFoundException('translation', $translation->getKey(), 'key');
            }

            $t = new Translation();
            $t->setDomain(TranslatorServiceInterface::DOMAIN);
            $t->setKey($translation->getKey());
            $t->setType($translation->getType());
            $t->addTranslation($locale, $translation->getTranslation());
            $t->setCreationDate(time());
            $t->setModificationDate(time());
            $t->save();
        }
    }

    public function deleteTranslation(string $key): void
    {
        $translation = $this->getTranslationByKey($key);
        if ($translation === null) {
            throw new NotFoundException('translation', $key, 'key');
        }

        $translation->delete();
    }

    private function getTranslationList(): Listing
    {
        $list = new Translation\Listing();
        $list->setDomain(TranslatorServiceInterface::DOMAIN);
        $list->setOrder('asc');
        $list->setOrderKey('translations_' . TranslatorServiceInterface::DOMAIN . '.key', false);

        return $list;
    }

    private function setNewValues(Translation $translation, array $languages): void
    {
        foreach ($languages as $language) {
            $translation->addTranslation($language, '');
        }
    }

    private function getTranslationByKey(string $key): ?Translation
    {
        $list = $this->getTranslationList();
        $list->setLimit(1);
        $list->addConditionParam('`key` = ?', $key);

        return !empty($list->load()) ? $list->current() : null;
    }

    /**
     * @throws InvalidLocaleException
     */
    private function validateLocale(string $locale, array $validLanguages): void
    {
        if (!in_array($locale, $validLanguages, true)) {
            throw new InvalidLocaleException($locale);
        }
    }
}
