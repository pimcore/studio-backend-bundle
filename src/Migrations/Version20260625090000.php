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

namespace Pimcore\Bundle\StudioBackendBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;

/**
 * @internal
 */
final class Version20260625090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add elementType column to the saved search configuration table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(SavedSearchConfiguration::TABLE_NAME)) {
            return;
        }

        $table = $schema->getTable(SavedSearchConfiguration::TABLE_NAME);
        if (!$table->hasColumn('elementType')) {
            $table->addColumn('elementType', 'string', ['notnull' => false, 'length' => 50]);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(SavedSearchConfiguration::TABLE_NAME)) {
            return;
        }

        $table = $schema->getTable(SavedSearchConfiguration::TABLE_NAME);
        if ($table->hasColumn('elementType')) {
            $table->dropColumn('elementType');
        }
    }
}
