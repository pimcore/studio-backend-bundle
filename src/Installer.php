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
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @internal
 */
final class Installer extends SettingsStoreAwareInstaller
{
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
        $this->executeDiffSql($schema);

        parent::install();
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
