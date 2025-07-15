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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CreateTranslationData;
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
        private Connection $db,
        private AdminResolverInterface $adminResolver,
    ) {
    }

    public function createTranslations(array $translationData): void
    {
        $languages = $this->adminResolver->getLanguages();

        /** @var CreateTranslationData $translation */
        foreach ($translationData as $translation) {
            if ($this->getTranslationByKey($translation->getKey(), $translation->getDomain()) !== null) {
                continue;
            }

            $this->createTranslationEntry(
                $translation->getKey(),
                $translation->getType(),
                $languages,
                $translation->getDomain()
            );
        }
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
            $entry = $this->getTranslationByKey($translation->getKey(), $translation->getDomain());
            if ($entry === null) {
                throw new NotFoundException('translation', $translation->getKey(), 'key');
            }

            $entry->addTranslation($locale, $translation->getTranslation());
            $entry->setType($translation->getType());
            $entry->setModificationDate(time());
            $entry->save();
        }
    }

    public function deleteTranslation(string $key, string $domain): void
    {
        $translation = $this->getTranslationByKey($key, $domain);
        if ($translation === null) {
            throw new NotFoundException('translation', $key, 'key');
        }

        $translation->delete();
    }

    private function getTableNameByDomain(string $domain): string
    {
        $translation = new Translation();
        $translation->setDomain($domain);

        return $translation->getDao()->getDatabaseTableName();
    }

    /**
     * This joins the language by key to their own columns in the listing.
     * This is useful for listing translations in the grid with filters and pagination.
     */
    public function joinLanguageColumns(Listing $listing, array $languages, string $domain): Listing
    {
        $db = $this->db;
        $tableName = $this->getTableNameByDomain($domain);
        $listing->onCreateQueryBuilder(
            function (DoctrineQueryBuilder $select) use ($languages, $tableName, $db) {

                $alreadyJoined = [];
                foreach ($languages as $join) {
                    if (in_array($join, $alreadyJoined)) {
                        continue;
                    }

                    $select->addSelect($join . '.text AS ' . $join);
                    $select->leftJoin(
                        $tableName,
                        $tableName,
                        $join,
                        '('
                        . $join . '.key = ' . $tableName . '.key'
                        . ' and ' . $join . '.language = ' . $db->quote($join)
                        . ')'
                    );
                    $alreadyJoined[] = $join;
                }
            }
        );

        return $listing;
    }

    public function addSearchCondition(Listing $listing, string $searchTerm): Listing
    {
        $tableName = $this->getTableNameByDomain($listing->getDomain());
        $listing->addConditionParam(
            '(lower(' . $tableName . '.key) LIKE :filterTerm OR lower(' . $tableName . '.text) LIKE :filterTerm)',
            ['filterTerm' => '%' . mb_strtolower($searchTerm) . '%']
        );

        return  $listing;
    }

    private function createTranslationEntry(string $key, string $type, array $languages, string $domain): void
    {
        $t = new Translation();
        $t->setDomain($domain);
        $t->setKey($key);
        $t->setType($type);
        $t->setCreationDate(time());
        $t->setModificationDate(time());
        $this->setNewValues($t, $languages);
        $t->save();
    }

    public function getTranslationList(string $domain = TranslatorServiceInterface::DOMAIN): Listing
    {
        $list = new Translation\Listing();
        $list->setDomain($domain);
        $list->setOrder('asc');
        $list->setOrderKey('translations_' . $domain . '.key', false);

        return $list;
    }

    private function setNewValues(Translation $translation, array $languages): void
    {
        foreach ($languages as $language) {
            $translation->addTranslation($language, '');
        }
    }

    public function getTranslationKeysWithTextFilter(
        string $filterTerm,
        string $language,
        string $domain = TranslatorServiceInterface::DOMAIN,
    ): array {
        $list = $this->getTranslationList($domain);
        $list->addConditionParam('language=? AND text LIKE ?', [$language, '%'.$filterTerm.'%']);

        $keys = [];

        foreach ($list->getTranslations() as $translation) {
            $keys[] = $translation->getKey();
        }

        return $keys;
    }

    private function getTranslationByKey(string $key, string $domain): ?Translation
    {
        $list = $this->getTranslationList($domain);
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
