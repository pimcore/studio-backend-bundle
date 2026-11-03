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
use Doctrine\DBAL\ParameterType;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Dao as CollectionConfigDao;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Dao as CollectionGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use function sprintf;

/**
 * @internal
 */
final readonly class CollRelationRepository implements CollRelationRepositoryInterface
{
    /**
     * The cursor an export pages these relations by: the identifier pair the artifact sorts them
     * by, with the id pair - the relation's whole primary key - behind it, so that the comparison
     * is total even for a row whose endpoints carry no identifier yet.
     *
     * A cursor rather than an offset, because an export is a read per page and a relation deleted
     * between two of them shifts every later one towards the start, past an offset that has already
     * counted it.
     */
    private const string CURSOR_CONDITION = " AND (COALESCE(`c`.`identifier`, ''),"
        . " COALESCE(`g`.`identifier`, ''), `r`.`colId`, `r`.`groupId`) > (?, ?, ?, ?)";

    public function __construct(
        private ListingFilterInterface $listingFilter,
        private Connection $connection,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListing(FilterParameter $parameters, int $colId): Listing
    {
        $listing = new Listing();
        // own parameter name, so a client supplied equals filter on `colId` narrows the route's
        // collection binding instead of replacing the condition Pimcore keys by its own string
        $listing->addConditionParam('`colId` = :scopeColId', ['scopeColId' => $colId]);

        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfter(int $storeId, ?ScopedCollectionRelation $after, int $limit): array
    {
        $query = sprintf(
            'SELECT `r`.`colId`, `r`.`groupId`, `r`.`sorter`,'
            . ' `c`.`identifier` AS `collectionIdentifier`, `g`.`identifier` AS `groupIdentifier`'
            . ' FROM `%s` `r`'
            . ' INNER JOIN `%s` `c` ON `c`.`id` = `r`.`colId`'
            . ' INNER JOIN `%s` `g` ON `g`.`id` = `r`.`groupId`'
            . ' WHERE `c`.`storeId` = ?%s'
            . ' ORDER BY `c`.`identifier`, `g`.`identifier`, `r`.`colId`, `r`.`groupId`'
            . ' LIMIT ?',
            CollectionGroupRelationDao::TABLE_NAME_RELATIONS,
            CollectionConfigDao::TABLE_NAME_COLLECTIONS,
            GroupConfigDao::TABLE_NAME_GROUPS,
            $after === null ? '' : self::CURSOR_CONDITION
        );

        $parameters = [$storeId];
        $types = [ParameterType::INTEGER];

        if ($after !== null) {
            $cursor = $after->getRelation();
            $parameters[] = $after->getCollectionIdentifier();
            $parameters[] = $after->getGroupIdentifier();
            $parameters[] = (int) $cursor->getColId();
            $parameters[] = (int) $cursor->getGroupId();
            $types[] = ParameterType::STRING;
            $types[] = ParameterType::STRING;
            $types[] = ParameterType::INTEGER;
            $types[] = ParameterType::INTEGER;
        }

        $parameters[] = $limit;
        $types[] = ParameterType::INTEGER;

        try {
            // the paging bounds are bound as integers, which is what the server needs them to be
            $rows = $this->connection->fetchAllAssociative($query, $parameters, $types);
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to read the collection relations: ' . $e->getMessage(), $e);
        }

        $relations = [];
        foreach ($rows as $row) {
            $relation = new CollectionGroupRelation();
            $relation->setColId((int) $row['colId']);
            $relation->setGroupId((int) $row['groupId']);
            $relation->setSorter((int) $row['sorter']);

            $relations[] = new ScopedCollectionRelation(
                $relation,
                (string) $row['collectionIdentifier'],
                (string) $row['groupIdentifier']
            );
        }

        return $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdate(int $colId, int $groupId, int $sorter): CollectionGroupRelation
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId($colId);
        $relation->setGroupId($groupId);
        $relation->setSorter($sorter);

        try {
            $relation->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $colId, int $groupId): void
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId($colId);
        $relation->setGroupId($groupId);
        $relation->delete();
    }
}
