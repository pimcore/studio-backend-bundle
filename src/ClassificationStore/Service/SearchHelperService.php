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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Listing as CollectionConfigListing;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing as GroupConfigListing;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing as KeyConfigListing;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing as KeyGroupRelationListing;

/**
 * @internal
 */
final readonly class SearchHelperService implements SearchHelperServiceInterface
{
    public function __construct(
        private TranslationRepositoryInterface $translationRepository,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function applySearchTermFilter(GroupConfigListing|CollectionConfigListing $list, ?string $searchTerm): void
    {
        if ($searchTerm === null || trim($searchTerm) === '') {
            return;
        }

        $searchTerms = $this->getTranslatedSearchFilterTerms($searchTerm);

        $conditions = [];
        $preparedSearchTerms = [];
        foreach ($searchTerms as $term) {
            $conditions[] = 'name LIKE ? OR description LIKE ?';
            $preparedSearchTerms[] = '%' . $term . '%';
            $preparedSearchTerms[] = '%' . $term . '%';
        }

        $list->addConditionParam('(' . implode(' OR ', $conditions) . ')', $preparedSearchTerms);
    }

    public function applyKeySearchFilter(KeyConfigListing $listing, ?string $searchTerm): void
    {
        if ($searchTerm === null || trim($searchTerm) === '') {
            return;
        }

        $listing->addConditionParam(
            '(name LIKE ? OR description LIKE ?)',
            ['%' . $searchTerm . '%', '%' . $searchTerm . '%']
        );
    }

    public function applyKeyGroupRelationSearchFilter(
        KeyGroupRelationListing $listing,
        ?string $searchTerm,
    ): void {
        if ($searchTerm === null || trim($searchTerm) === '') {
            return;
        }

        $searchTerms = $this->getTranslatedSearchFilterTerms($searchTerm);
        $searchFilterConditions = [];

        foreach ($searchTerms as $term) {
            $searchFilterConditions[] =
                KeyConfigDao::TABLE_NAME_KEYS . '.name LIKE ' . $listing->quote('%' . $term . '%')
                . ' OR '
                . GroupConfigDao::TABLE_NAME_GROUPS . '.name LIKE ' . $listing->quote('%' . $term . '%')
                . ' OR '
                . KeyConfigDao::TABLE_NAME_KEYS . '.description LIKE ' . $listing->quote('%' . $term . '%');
        }

        $listing->setResolveGroupName(true);
        $listing->addConditionParam('(' . implode(' OR ', $searchFilterConditions) . ')');
    }

    public function getTranslatedSearchFilterTerms(string $searchTerm): array
    {
        try {
            $user = $this->securityService->getCurrentUser();

            $translatedSearchKeys = $this->translationRepository->getTranslationKeysWithTextFilter(
                $searchTerm,
                $user->getLanguage()
            );

            $searchTerms = array_merge([$searchTerm], $translatedSearchKeys);
        } catch (UserNotFoundException) {
            $searchTerms = [$searchTerm];
        }

        return $searchTerms;
    }
}
