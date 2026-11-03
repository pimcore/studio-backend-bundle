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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\StoreConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Dao as CollectionConfigDao;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig\Dao as StoreConfigDao;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig\Listing;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao as SubGroupConfigDao;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class StoreRepository implements StoreRepositoryInterface
{
    /**
     * The levels an identifier gap is reported under, each paired with the table the probe below
     * reads it from and the column that scopes that table to one store.
     */
    private const array IDENTIFIER_LEVELS = [
        'store' => [StoreConfigDao::TABLE_NAME_STORES, 'id'],
        'sub group' => [SubGroupConfigDao::TABLE_NAME_SUBGROUPS, 'storeId'],
        'collection' => [CollectionConfigDao::TABLE_NAME_COLLECTIONS, 'storeId'],
        'group' => [GroupConfigDao::TABLE_NAME_GROUPS, 'storeId'],
        'key' => [KeyConfigDao::TABLE_NAME_KEYS, 'storeId'],
    ];

    public function __construct(
        private StoreConfigResolverInterface $storeConfigResolver,
        private Connection $connection,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listStores(): array
    {
        return (new Listing())->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): StoreConfig
    {
        $config = $this->storeConfigResolver->getById($id);

        if (!$config) {
            throw new NotFoundException('store configuration', $id);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getByIdentifier(string $identifier): ?StoreConfig
    {
        $listing = new Listing();
        $listing->addConditionParam('`identifier` = :identifier', ['identifier' => $identifier]);

        return $listing->load()[0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function findEntitiesWithoutIdentifier(int $storeId): array
    {
        $entities = [];

        // one statement per level, so that the level stays a PHP value rather than SQL text
        foreach (self::IDENTIFIER_LEVELS as $level => [$table, $scopeColumn]) {
            try {
                $names = $this->connection->fetchFirstColumn(
                    sprintf(
                        'SELECT `name` FROM `%s` WHERE `%s` = ? AND `identifier` IS NULL ORDER BY `name`',
                        $table,
                        $scopeColumn
                    ),
                    [$storeId]
                );
            } catch (DBALException $e) {
                throw new DatabaseException(
                    'Failed to check the store for identifier gaps: ' . $e->getMessage(),
                    $e
                );
            }

            foreach ($names as $name) {
                $entities[] = ['level' => $level, 'name' => is_string($name) ? $name : ''];
            }
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function isNameTaken(string $name, ?int $exceptId): bool
    {
        $existing = $this->storeConfigResolver->getByName($name);

        return $existing !== null && $existing->getId() !== $exceptId;
    }

    /**
     * {@inheritdoc}
     */
    public function saveFromArtifact(
        ?StoreConfig $config,
        string $identifier,
        string $name,
        ?string $description,
    ): StoreConfig {
        $config ??= new StoreConfig();
        $config->setName($name);
        $config->setDescription($description ?? '');

        try {
            // before the save, so that a new row carries the file's identifier instead of a fresh one
            $config->setIdentifier($identifier);
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($config->getId(), $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name): StoreConfig
    {
        $existing = $this->storeConfigResolver->getByName($name);

        if ($existing) {
            throw new InvalidArgumentException(
                sprintf('Store with the name "%s" already exists', $name)
            );
        }

        $config = new StoreConfig();
        $config->setName($name);

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
    public function update(int $id, string $name, ?string $description): StoreConfig
    {
        $config = $this->getById($id);

        $existing = $this->storeConfigResolver->getByName($name);
        if ($existing && $existing->getId() !== $id) {
            throw new InvalidArgumentException(
                sprintf('Store with the name "%s" already exists', $name)
            );
        }

        $config->setName($name);
        $config->setDescription($description ?? '');

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }

        return $config;
    }
}
