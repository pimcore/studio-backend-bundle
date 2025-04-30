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
use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;

/**
 * @internal
 */
final class CollectionRelationsRepository implements CollectionRelationsRepositoryInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getCollectionIdsWith(array $groupIds): array
    {
        $collectionIds = [];
        $queryBuilder = $this->dbResolver->get()->createQueryBuilder();

        $queryBuilder
            ->select('colId')
            ->from('classificationstore_collectionrelations')
            ->where('groupId IN (:groupIds)');

        $queryBuilder->setParameter('groupIds', $groupIds, ArrayParameterType::INTEGER);

        try {
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();

            foreach ($result as $row) {
                $collectionIds[] = $row['colId'];
            }

            return $collectionIds;
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getFromCollection(int $collectionId): array
    {
        $listing = new CollectionGroupRelation\Listing();
        $listing->setCondition('colId = ?', $collectionId);

        return $listing->load();
    }
}
