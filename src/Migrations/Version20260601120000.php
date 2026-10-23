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
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;

/**
 * @internal
 */
final class Version20260601120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase classId column length from 10 to 50 '.
            'in grid configuration and grid configuration favorite tables';
    }

    public function up(Schema $schema): void
    {
        $this->increaseClassIdLength($schema, GridConfiguration::TABLE_NAME);
        $this->increaseClassIdLength($schema, GridConfigurationFavorite::TABLE_NAME);
    }

    private function increaseClassIdLength(Schema $schema, string $tableName): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        $column = $table->getColumn('classId');
        $column->setLength(50);
    }
}
