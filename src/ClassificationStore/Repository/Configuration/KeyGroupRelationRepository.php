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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;

/**
 * @internal
 */
final readonly class KeyGroupRelationRepository implements KeyGroupRelationRepositoryInterface
{
    public function __construct(
        private ListingFilterInterface $listingFilter,
        private Connection $connection,
        private ArtifactEntryMapperInterface $entryMapper,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListingByGroupId(FilterParameter $parameters, int $groupId): Listing
    {
        $listing = new Listing();
        // own parameter name, so a client supplied equals filter on `groupId` narrows the route's
        // group binding instead of replacing the condition Pimcore keys by its own string
        $listing->addConditionParam('`groupId` = :scopeGroupId', ['scopeGroupId' => $groupId]);

        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkByStore(int $storeId, int $limit, int $offset): array
    {
        $query = sprintf(
            'SELECT `r`.`keyId`, `r`.`groupId`, `r`.`sorter`, `r`.`mandatory`, `r`.`metadata`,'
            . ' `r`.`definitionOverride`, `r`.`subGroup`,'
            . ' `g`.`identifier` AS `groupIdentifier`, `k`.`identifier` AS `keyIdentifier`'
            . ' FROM `%s` `r`'
            . ' INNER JOIN `%s` `g` ON `g`.`id` = `r`.`groupId`'
            . ' INNER JOIN `%s` `k` ON `k`.`id` = `r`.`keyId`'
            . ' WHERE `g`.`storeId` = ?'
            . ' ORDER BY `g`.`identifier`, `k`.`identifier`, `r`.`groupId`, `r`.`keyId`'
            . ' LIMIT ? OFFSET ?',
            KeyGroupRelationDao::TABLE_NAME_RELATIONS,
            GroupConfigDao::TABLE_NAME_GROUPS,
            KeyConfigDao::TABLE_NAME_KEYS
        );

        $assignments = [];

        try {
            // the paging bounds are bound as integers, which is what the server needs them to be
            $rows = $this->connection->fetchAllAssociative(
                $query,
                [$storeId, $limit, $offset],
                [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER]
            );

            foreach ($rows as $row) {
                $assignments[] = new ScopedKeyAssignment(
                    $this->hydrateRelation($row),
                    (string) $row['groupIdentifier'],
                    (string) $row['keyIdentifier']
                );
            }
        } catch (DBALException|Exception $e) {
            throw new DatabaseException('Failed to read the key assignments: ' . $e->getMessage(), $e);
        }

        return $assignments;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupNamesByKeyId(int $keyId): array
    {
        try {
            return $this->connection->fetchFirstColumn(
                sprintf(
                    'SELECT DISTINCT `g`.`name` FROM `%s` `r`'
                    . ' INNER JOIN `%s` `g` ON `g`.`id` = `r`.`groupId`'
                    . ' WHERE `r`.`keyId` = ? ORDER BY `g`.`name`',
                    KeyGroupRelationDao::TABLE_NAME_RELATIONS,
                    GroupConfigDao::TABLE_NAME_GROUPS
                ),
                [$keyId]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to read the groups of the key: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdateFromArtifact(
        ArtifactAssignmentEntry $entry,
        int $keyId,
        int $groupId,
    ): KeyGroupRelation {
        try {
            // the mapper sets only what the entry supplies, which is what the model's own
            // initialized guards gate the write on
            $relation = $this->entryMapper->mapToAssignment($entry, $keyId, $groupId);
            $relation->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdate(int $keyId, int $groupId, int $sorter, bool $mandatory): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->setSorter($sorter);
        $relation->setMandatory($mandatory);

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
    public function delete(int $keyId, int $groupId): void
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->delete();
    }

    /**
     * The stored row as the model carries it: the own columns only, the two JSON ones decoded. The
     * three per assignment columns are set from the row, which is what marks them as loaded.
     *
     * @param array<string, mixed> $row
     */
    private function hydrateRelation(array $row): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId((int) $row['keyId']);
        $relation->setGroupId((int) $row['groupId']);
        $relation->setSorter((int) $row['sorter']);
        $relation->setMandatory((bool) $row['mandatory']);
        $relation->setMetadata($this->decodeJsonColumn($row['metadata'] ?? null));
        $relation->setDefinitionOverride($this->decodeJsonColumn($row['definitionOverride'] ?? null));

        $subGroup = $row['subGroup'] ?? null;
        $relation->setSubGroup(is_string($subGroup) ? $subGroup : null);

        return $relation;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonColumn(mixed $value): ?array
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
