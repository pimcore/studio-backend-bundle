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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing;
use function array_fill;
use function count;
use function implode;
use function sprintf;

/**
 * @internal
 */
final readonly class SubGroupRepository implements SubGroupRepositoryInterface
{
    private const string NOT_FOUND_TYPE = 'sub group configuration';

    public function __construct(
        private ListingFilterInterface $listingFilter,
        private SearchHelperServiceInterface $searchHelperService,
        private Connection $connection,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing
    {
        $listing = new Listing();
        $listing->addConditionParam('`storeId` = :storeId', ['storeId' => $storeId]);

        $this->applySearchCondition($listing, $parameters);
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): SubGroupConfig
    {
        $listing = new Listing();
        $listing->addConditionParam('`id` = :id', ['id' => $id]);
        $configs = $listing->load();

        if (count($configs) === 0) {
            throw new NotFoundException(self::NOT_FOUND_TYPE, $id);
        }

        return $configs[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getByIdentifiers(int $storeId, array $identifiers): array
    {
        if (count($identifiers) === 0) {
            return [];
        }

        // positional throughout, so that the IN list expands the way the other listings build theirs
        $placeholders = implode(',', array_fill(0, count($identifiers), '?'));

        $listing = new Listing();
        $listing->addConditionParam('`storeId` = ?', [$storeId]);
        $listing->addConditionParam('`identifier` IN (' . $placeholders . ')', $identifiers);

        $resolved = [];
        foreach ($listing->load() as $config) {
            $identifier = $config->getIdentifier();
            if ($identifier === null) {
                continue;
            }

            $resolved[$identifier] = $config;
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, int $storeId): SubGroupConfig
    {
        $this->assertNameIsFree($name, $storeId);

        $config = new SubGroupConfig();
        $config->setName($name);
        $config->setStoreId($storeId);

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, string $name, ?string $description): SubGroupConfig
    {
        $config = $this->getById($id);
        $this->assertNameIsFree($name, $config->getStoreId(), $id);

        $config->setName($name);
        $config->setDescription($description);

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): void
    {
        $config = $this->getById($id);
        $usedBy = $this->getGroupNamesUsing($config->getStoreId(), $config->getIdentifier());

        if (count($usedBy) > 0) {
            throw new ConflictException(
                sprintf(
                    'Sub group "%s" is still assigned to keys of the group(s) "%s" and cannot be deleted',
                    $config->getName(),
                    implode('", "', $usedBy)
                )
            );
        }

        $config->delete();
    }

    /**
     * The groups of the store whose key assignments carry the identifier. Scoped to the store
     * because the identifier is unique per store, not globally.
     *
     * @return string[]
     *
     * @throws DatabaseException
     */
    private function getGroupNamesUsing(int $storeId, ?string $identifier): array
    {
        if ($identifier === null) {
            return [];
        }

        try {
            return $this->connection->fetchFirstColumn(
                sprintf(
                    'SELECT DISTINCT `g`.`name` FROM `%s` `r`'
                    . ' INNER JOIN `%s` `g` ON `g`.`id` = `r`.`groupId`'
                    . ' WHERE `g`.`storeId` = ? AND `r`.`subGroup` = ?',
                    KeyGroupRelationDao::TABLE_NAME_RELATIONS,
                    GroupConfigDao::TABLE_NAME_GROUPS
                ),
                [$storeId, $identifier]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to check the sub group for usage: ' . $e->getMessage(), $e);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertNameIsFree(string $name, int $storeId, ?int $exceptId = null): void
    {
        $listing = new Listing();
        $listing->addConditionParam('`name` = :name', ['name' => $name]);
        $listing->addConditionParam('`storeId` = :storeId', ['storeId' => $storeId]);

        if ($exceptId !== null) {
            $listing->addConditionParam('`id` != :exceptId', ['exceptId' => $exceptId]);
        }

        if (count($listing->load()) === 0) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf('Sub group with the name "%s" already exists in store %d', $name, $storeId)
        );
    }

    private function applySearchCondition(Listing $listing, FilterParameter $parameters): void
    {
        $searchFilter = $parameters->getSimpleColumnFilterByType(FilterType::SEARCH->value);
        if (!$searchFilter) {
            return;
        }

        $this->searchHelperService->applySearchTermFilter($listing, $searchFilter->getFilterValue());
    }
}
