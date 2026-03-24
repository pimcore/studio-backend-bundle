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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CreateTranslationData;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\UpdateTranslation;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\Translation;
use Pimcore\Model\Translation\Listing;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class TranslationRepository implements TranslationRepositoryInterface
{
    private array $validLanguages;
    
    public function __construct(
        private SettingsProviderInterface $systemSettingsProvider,
        private Connection $db,
        private SecurityServiceInterface $securityService,
    ) {
        $settings = $this->systemSettingsProvider->getSettings();
        $this->validLanguages = $settings['validLanguages'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function createTranslations(bool $throwError, array $translationData): void
    {
        $hasPermissions = $this->securityService->getCurrentUser()->isAllowed(UserPermissions::TRANSLATIONS->value);

        /** @var CreateTranslationData $translation */
        foreach ($translationData as $translation) {
            $domain = $translation->getDomain();
            if ($domain !== TranslatorServiceInterface::DOMAIN && $hasPermissions === false
            ) {
                throw new ForbiddenException('You do not have required permissions for this action');
            }

            if ($this->getTranslationByKey($translation->getKey(), $translation->getDomain()) !== null) {
                if ($throwError) {
                    throw new ElementExistsException(
                        sprintf('Translation with the key (%s) already exists.', $translation->getKey())
                    );
                }

                continue;
            }

            $this->createTranslationEntry(
                $translation->getKey(),
                $translation->getType(),
                $translation->getDomain()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslations(string $domain, UpdateTranslation $updateData): void
    {
        $translation = $this->getTranslationByKey($updateData->getKey(), $domain);
        if ($translation === null) {
            throw new NotFoundException('translation', $updateData->getKey(), 'key');
        }
        if ($updateData->getType() !== null) {
            $translation->setType($updateData->getType());
        }
        $translation->setModificationDate(time());

        foreach ($updateData->getTranslationData() as $data) {
            $this->validateLocale($data->getLocale());
            $translation->addTranslation($data->getLocale(), $data->getTranslation());
        }

        $translation->save();
    }

    public function deleteTranslation(string $key, string $domain): void
    {
        $translation = $this->getTranslationByKey($key, $domain);
        if ($translation === null) {
            throw new NotFoundException('translation', $key, 'key');
        }

        $translation->delete();
    }

    public function createDummyTranslation(string $domain, array $allowedLanguages): Translation
    {
        $translation = new Translation();
        if (in_array($domain, [TranslatorServiceInterface::DOMAIN, 'admin'], true)) {
            $translation->setDomain($domain);
        }

        foreach ($allowedLanguages as $language) {
            $translation->addTranslation($language, '');
        }

        return $translation;
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
                    if (in_array($join, $alreadyJoined, true)) {
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
        $key = $tableName . '.key';
        $text = $tableName . '.text';
        $listing->addConditionParam(
            '(lower(' . $key . ') LIKE :filterTerm OR lower('. $text .') LIKE :filterTerm)',
            ['filterTerm' => '%' . mb_strtolower($searchTerm) . '%']
        );

        return $listing;
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

    public function getTranslationList(string $domain = TranslatorServiceInterface::DOMAIN): Listing
    {
        $list = new Translation\Listing();
        $list->setDomain($domain);
        $list->setOrder('asc');
        $list->setOrderKey('translations_' . $domain . '.key', false);

        return $list;
    }

    private function getTableNameByDomain(string $domain): string
    {
        $translation = new Translation();
        $translation->setDomain($domain);

        return $translation->getDao()->getDatabaseTableName();
    }

    private function createTranslationEntry(string $key, string $type, string $domain): void
    {
        $t = new Translation();
        $t->setDomain($domain);
        $t->setKey($key);
        $t->setType($type);
        $t->setCreationDate(time());
        $t->setModificationDate(time());
        $this->setNewValues($t);
        $t->save();
    }

    private function setNewValues(Translation $translation): void
    {
        foreach ($this->validLanguages as $language) {
            $translation->addTranslation($language, '');
        }
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
    private function validateLocale(string $locale): void
    {
        if (!in_array($locale, $this->validLanguages, true)) {
            throw new InvalidLocaleException($locale);
        }
    }
}
