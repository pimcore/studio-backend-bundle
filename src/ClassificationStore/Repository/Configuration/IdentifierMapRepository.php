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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use function array_unique;
use function array_values;
use function sprintf;

/**
 * @internal
 */
final readonly class IdentifierMapRepository implements IdentifierMapRepositoryInterface
{
    private const string IDENTIFIER_QUERY =
        'SELECT `id`, `identifier` FROM `%s` WHERE `storeId` = ? AND `identifier` IN (?)';

    private const string ID_QUERY =
        'SELECT `id`, `identifier` FROM `%s` WHERE `storeId` = ? AND `id` IN (?) AND `identifier` IS NOT NULL';

    public function __construct(private Connection $connection)
    {
    }

    public function getGroupIdentifiersByIds(int $storeId, array $ids): array
    {
        return $this->identifiersByIds(GroupConfigDao::TABLE_NAME_GROUPS, 'group', $storeId, $ids);
    }

    public function getGroupIdsByIdentifiers(int $storeId, array $identifiers): array
    {
        return $this->idsByIdentifiers(GroupConfigDao::TABLE_NAME_GROUPS, 'groups', $storeId, $identifiers);
    }

    public function getKeyIdentifiersByIds(int $storeId, array $ids): array
    {
        return $this->identifiersByIds(KeyConfigDao::TABLE_NAME_KEYS, 'key', $storeId, $ids);
    }

    public function getKeyIdsByIdentifiers(int $storeId, array $identifiers): array
    {
        return $this->idsByIdentifiers(KeyConfigDao::TABLE_NAME_KEYS, 'keys', $storeId, $identifiers);
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, string>
     *
     * @throws DatabaseException
     */
    private function identifiersByIds(string $table, string $subject, int $storeId, array $ids): array
    {
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return [];
        }

        try {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(self::ID_QUERY, $table),
                [$storeId, $ids],
                [ParameterType::INTEGER, ArrayParameterType::INTEGER]
            );
        } catch (DBALException $e) {
            throw new DatabaseException(
                sprintf('Failed to read the %s identifiers: ', $subject) . $e->getMessage(),
                $e
            );
        }

        $identifiers = [];
        foreach ($rows as $row) {
            $identifiers[(int) $row['id']] = (string) $row['identifier'];
        }

        return $identifiers;
    }

    /**
     * @param string[] $identifiers
     *
     * @return array<string, int>
     *
     * @throws DatabaseException
     */
    private function idsByIdentifiers(string $table, string $subject, int $storeId, array $identifiers): array
    {
        $identifiers = array_values(array_unique($identifiers));

        if (empty($identifiers)) {
            return [];
        }

        try {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(self::IDENTIFIER_QUERY, $table),
                [$storeId, $identifiers],
                [ParameterType::INTEGER, ArrayParameterType::STRING]
            );
        } catch (DBALException $e) {
            throw new DatabaseException(
                sprintf('Failed to read the %s by identifier: ', $subject) . $e->getMessage(),
                $e
            );
        }

        $resolved = [];
        foreach ($rows as $row) {
            $resolved[(string) $row['identifier']] = (int) $row['id'];
        }

        return $resolved;
    }
}
