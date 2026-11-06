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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use function array_unique;
use function array_values;
use function count;
use function sprintf;

/**
 * @internal
 */
final readonly class KeyGroupRelationRepository implements KeyGroupRelationRepositoryInterface
{
    /**
     * Every column, rather than `id` and `localized`: `composer.json` allows a core whose
     * `classificationstore_keys` has no `localized` column, and naming the column would fail against
     * one of those instead of reporting that no key states a flag. This is the single statement the
     * per-row `KeyConfig::getById()` calls collapse into, and it reads the same rows those did.
     */
    private const string LOCALIZATION_QUERY = 'SELECT * FROM `%s` WHERE `id` IN (?)';

    public function __construct(
        private GroupConfigRepositoryInterface $groupConfigRepository,
        private SearchHelperServiceInterface $searchHelperService,
        private DbResolverInterface $dbResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedKeyGroupRelationByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $groupIds = null,
        ?string $searchTerm = null
    ): array {
        $listing = $this->getBaseListing($storeId, $groupIds, $searchTerm);
        $listing->setOffset($this->getOffset($collectionParameters));
        $listing->setLimit($collectionParameters->getPageSize());

        return $listing->getList();
    }

    public function getCountByStoreId(int $storeId, ?array $groupIds = null, ?string $searchTerm = null): int
    {
        return $this->getBaseListing($storeId, $groupIds, $searchTerm)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getByGroupIds(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }

        $listing = new Listing();
        $this->applyGroupKeyOrder($listing);
        $this->applyGroupIdsFilter($listing, $groupIds);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyLocalizationFlags(array $keyIds): array
    {
        $keyIds = array_values(array_unique($keyIds));

        if (empty($keyIds)) {
            return [];
        }

        try {
            $rows = $this->dbResolver->get()->fetchAllAssociative(
                sprintf(self::LOCALIZATION_QUERY, KeyConfigDao::TABLE_NAME_KEYS),
                [$keyIds],
                [ArrayParameterType::INTEGER]
            );
        } catch (DBALException $e) {
            throw new DatabaseException(
                'Failed to read the localization flags of the keys: ' . $e->getMessage(),
                $e
            );
        }

        $flags = [];

        foreach ($rows as $row) {
            $flag = $row[KeyLocalizationResolverInterface::LOCALIZED_COLUMN] ?? null;
            $flags[(int) $row['id']] = $flag === null ? null : (bool) $flag;
        }

        return $flags;
    }

    private function getBaseListing(int $storeId, ?array $groupIds = null, ?string $searchTerm = null): Listing
    {
        $groupIds = array_map(
            static fn ($group) => $group->getId(),
            $this->groupConfigRepository->getAllGroupsByStore($storeId, $groupIds)
        );

        $listing = new Listing();
        $this->applyStoreKeyOrder($listing);
        $this->applyGroupIdsFilter($listing, $groupIds);

        $this->searchHelperService->applyKeyGroupRelationSearchFilter($listing, $searchTerm);

        return $listing;
    }

    private function applyGroupKeyOrder(Listing $listing): void
    {
        $listing->setOrderKey(['sorter', 'id']);
        $listing->setOrder(['ASC', 'ASC']);
    }

    private function applyStoreKeyOrder(Listing $listing): void
    {
        $listing->setOrderKey(['groupId', 'sorter', 'id']);
        $listing->setOrder(['ASC', 'ASC', 'ASC']);
    }

    private function applyGroupIdsFilter(Listing $list, array $groupIds): void
    {
        if (empty($groupIds)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $list->addConditionParam('groupID IN ('. $placeholders .')', $groupIds);
    }

    private function getOffset(CollectionParametersInterface $collectionParameters): int
    {
        return ($collectionParameters->getPage() - 1) * $collectionParameters->getPageSize();
    }
}
