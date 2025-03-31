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


namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;

/**
 * @internal
 */
final class CollectionRelationsRepository implements CollectionRelationsRepositoryInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
    )
    {
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
}