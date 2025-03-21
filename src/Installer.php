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

namespace Pimcore\Bundle\StudioBackendBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationShare;
use Pimcore\Bundle\StudioBackendBundle\Entity\Perspective\UserPerspectiveData;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @internal
 */
final class Installer extends SettingsStoreAwareInstaller
{
    private const array PERSPECTIVE_PERMISSIONS = [
        UserPermissions::PERSPECTIVE_EDITOR->value,
        UserPermissions::WIDGET_EDITOR->value,
    ];

    public function __construct(
        private readonly Connection $db,
        BundleInterface $bundle,
    ) {
        parent::__construct($bundle);
    }

    /**
     * @throws SchemaException|Exception
     */
    public function install(): void
    {
        $schema = $this->db->createSchemaManager()->introspectSchema();

        $this->createTranslationTable($schema);
        $this->createGridConfigurationTable($schema);
        $this->createGridConfigurationSharesTable($schema);
        $this->createGridConfigurationFavoritesTable($schema);
        $this->createUserPerspectivesTable($schema);
        $this->addUserPermission($schema);
        $this->executeDiffSql($schema);

        parent::install();
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    public function uninstall(): void
    {
        $schema = $this->db->createSchemaManager()->introspectSchema();

        if ($schema->hasTable(GridConfiguration::TABLE_NAME)) {
            $schema->dropTable(GridConfiguration::TABLE_NAME);
        }

        if ($schema->hasTable(GridConfigurationShare::TABLE_NAME)) {
            $schema->dropTable(GridConfigurationShare::TABLE_NAME);
        }

        if ($schema->hasTable(GridConfigurationFavorite::TABLE_NAME)) {
            $schema->dropTable(GridConfigurationFavorite::TABLE_NAME);
        }

        if ($schema->hasTable(UserPerspectiveData::TABLE_NAME)) {
            $schema->dropTable(UserPerspectiveData::TABLE_NAME);
        }
        $this->removeUserPermission($schema);

        $this->executeDiffSql($schema);

        parent::uninstall();
    }

    /**
     * @throws SchemaException
     */
    private function createTranslationTable(Schema $schema): void
    {
        $translationsDomainTableName = 'translations_' . TranslatorService::DOMAIN;
        if (!$schema->hasTable($translationsDomainTableName)) {
            $translationDomainTable = $schema->createTable($translationsDomainTableName);

            $translationDomainTable->addColumn('key', 'string', [
                'notnull' => true,
                'length' => 190,
            ]);

            $translationDomainTable->addColumn('type', 'string', [
                'notnull' => true,
                'length' => 15,
            ]);

            $translationDomainTable->addColumn('language', 'string', [
                'notnull' => true,
                'length' => 10,
            ]);

            $translationDomainTable->addColumn('text', 'text');

            $translationDomainTable->addColumn('creationDate', 'integer', [
                'unsigned' => true,
                'length' => 11,
            ]);

            $translationDomainTable->addColumn('modificationDate', 'integer', [
                'unsigned' => true,
                'length' => 11,
            ]);

            $translationDomainTable->addColumn('userOwner', 'integer', [
                'unsigned' => true,
                'length' => 11,
            ]);

            $translationDomainTable->addColumn('userModification', 'integer', [
                'unsigned' => true,
                'length' => 11,
            ]);

            $translationDomainTable->setPrimaryKey(['key', 'language'], 'pk_translation');
            $translationDomainTable->addIndex(['language'], 'idx_language');
        }
    }

    /**
     * @throws SchemaException
     */
    public function createGridConfigurationFavoritesTable(Schema $schema): void
    {
        if ($schema->hasTable(GridConfigurationFavorite::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(GridConfigurationFavorite::TABLE_NAME);

        $table->addColumn(
            'user',
            'integer',
            ['notnull' => true, 'unsigned' => true]
        );

        $table->addColumn(
            'configuration',
            'integer',
            ['notnull' => true, 'unsigned' => true]
        );

        $table->addColumn('folder', 'integer', [
            'notnull' => true,
            'unsigned' => true,
        ]);

        $table->addColumn('classId', 'string', [
            'notnull' => false,
            'length' => 10,
        ]);

        $table->addForeignKeyConstraint(
            'users',
            ['user'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.GridConfigurationFavorite::TABLE_NAME.'_users'
        );

        $table->addForeignKeyConstraint(
            GridConfiguration::TABLE_NAME,
            ['configuration'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.GridConfigurationFavorite::TABLE_NAME.'_configurations'
        );

        $table->setPrimaryKey(['user', 'configuration', 'folder'], 'pk_'.GridConfigurationFavorite::TABLE_NAME);
    }

    /**
     * @throws SchemaException
     */
    public function createGridConfigurationSharesTable(Schema $schema): void
    {
        if ($schema->hasTable(GridConfigurationShare::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(GridConfigurationShare::TABLE_NAME);

        $table->addColumn(
            'user',
            'integer',
            ['notnull' => false, 'unsigned' => true]
        );

        $table->addColumn(
            'configuration',
            'integer',
            ['notnull' => false, 'unsigned' => true]
        );

        $table->addForeignKeyConstraint(
            'users',
            ['user'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.GridConfigurationShare::TABLE_NAME.'_users'
        );

        $table->addForeignKeyConstraint(
            GridConfiguration::TABLE_NAME,
            ['configuration'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.GridConfigurationShare::TABLE_NAME.'_configurations'
        );

        $table->setPrimaryKey(['user', 'configuration'], 'pk_'.GridConfigurationShare::TABLE_NAME);
    }

    /**
     * @throws SchemaException
     */
    private function createGridConfigurationTable(Schema $schema): void
    {
        if ($schema->hasTable(GridConfiguration::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(GridConfiguration::TABLE_NAME);

        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'unsigned' => true,
        ]);

        $table->addColumn('assetFolderId', 'integer', [
            'notnull' => false,
            'unsigned' => true,
        ]);

        $table->addColumn('classId', 'string', ['notnull' => false, 'length' => 10]);

        $table->addColumn(
            'owner',
            'integer',
            ['notnull' => false, 'unsigned' => true]
        );

        $table->addColumn('name', 'string', ['notnull' => true]);
        $table->addColumn('description', 'text', ['notnull' => true]);

        $table->addColumn('pageSize', 'integer', [
            'notnull' => true,
            'unsigned' => true,
        ]);

        $table->addColumn('shareGlobal', 'boolean', ['notnull' => true]);
        $table->addColumn('saveFilter', 'boolean', ['notnull' => true]);
        $table->addColumn('columns', 'json', ['notnull' => true]);
        $table->addColumn('filter', 'json', ['notnull' => false]);
        $table->addColumn('creationDate', 'datetime', ['notnull' => false]);
        $table->addColumn('modificationDate', 'datetime', ['notnull' => false]);

        $table->setPrimaryKey(['id'], 'pk_'.GridConfiguration::TABLE_NAME);

        $table->addForeignKeyConstraint(
            'users',
            ['owner'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.GridConfiguration::TABLE_NAME.'_owner_users'
        );

        $table->addForeignKeyConstraint(
            'assets',
            ['assetFolderId'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.GridConfiguration::TABLE_NAME.'_assetFolderId_id'
        );
    }

    /**
     * @throws SchemaException
     */
    public function createUserPerspectivesTable(Schema $schema): void
    {
        if ($schema->hasTable(UserPerspectiveData::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(UserPerspectiveData::TABLE_NAME);

        $table->addColumn(
            'user',
            'integer',
            ['notnull' => true, 'unsigned' => true]
        );

        $table->addColumn('perspectives', 'text', [
            'notnull' => false,
        ]);

        $table->addColumn('activePerspective', 'string', [
            'notnull' => false,
            'length' => 255,
        ]);

        $table->addForeignKeyConstraint(
            'users',
            ['user'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_' . UserPerspectiveData::TABLE_NAME.'_users'
        );

        $table->setPrimaryKey(['user'], 'pk_' . UserPerspectiveData::TABLE_NAME);
    }

    /**
     * @throws Exception
     */
    private function addUserPermission(Schema $schema): void
    {
        if ($schema->hasTable(UserPermissions::DEFINITIONS_TABLE->value)) {
            foreach (self::PERSPECTIVE_PERMISSIONS as $permission) {
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder
                    ->insert(UserPermissions::DEFINITIONS_TABLE->value)
                    ->values([
                        $this->db->quoteIdentifier('key') => ':key',
                        $this->db->quoteIdentifier('category') => ':category',
                    ])
                    ->setParameters([
                        'key' => $permission,
                        'category' => UserPermissions::PERMISSIONS_CATEGORY->value,
                    ]);

                $queryBuilder->executeStatement();
            }
        }
    }

    /**
     * @throws Exception
     */
    private function removeUserPermission(Schema $schema): void
    {
        if ($schema->hasTable(UserPermissions::DEFINITIONS_TABLE->value)) {
            foreach (self::PERSPECTIVE_PERMISSIONS as $permission) {
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder
                    ->delete(UserPermissions::DEFINITIONS_TABLE->value)
                    ->where($this->db->quoteIdentifier('key') . ' = :key')
                    ->setParameter('key', $permission);

                $queryBuilder->executeStatement();
            }
        }
    }

    /**
     * @throws Exception
     */
    private function executeDiffSql(Schema $newSchema): void
    {
        $currentSchema = $this->db->createSchemaManager()->introspectSchema();
        $schemaComparator = new Comparator($this->db->getDatabasePlatform());
        $schemaDiff = $schemaComparator->compareSchemas($currentSchema, $newSchema);
        $dbPlatform = $this->db->getDatabasePlatform();
        if (!$dbPlatform instanceof AbstractPlatform) {
            throw new InstallationException('Could not get database platform.');
        }

        $sqlStatements = $dbPlatform->getAlterSchemaSQL($schemaDiff);

        if (!empty($sqlStatements)) {
            $this->db->executeStatement(implode(';', $sqlStatements));
        }
    }
}
