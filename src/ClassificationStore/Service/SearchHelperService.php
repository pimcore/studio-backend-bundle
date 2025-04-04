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


namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing as GroupConfigListing;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Listing as CollectionConfigListing;
use Pimcore\Model\Translation;

/**
 * @internal
 */
final readonly class SearchHelperService implements SearchHelperServiceInterface
{
    public function __construct(
        private TranslationRepositoryInterface $translationRepository,
        private SecurityServiceInterface $securityService
    )
    {
    }


    public function applySearchTermFilter(GroupConfigListing|CollectionConfigListing $list, string $searchTerm): void
    {
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

    public function getTranslatedSearchFilterTerms(string $searchTerm): array
    {
        try {
            $user = $this->securityService->getCurrentUser();

            $translatedSearchKeys = $this->translationRepository->getTranslationKeysWithTextFilter(
                $searchTerm,
                $user->getLanguage(),
                Translation::DOMAIN_ADMIN
            );

            $searchTerms = array_merge([$searchTerm], $translatedSearchKeys);
        }catch (UserNotFoundException) {
            $searchTerms = [$searchTerm];
        }

        return $searchTerms;
    }
}