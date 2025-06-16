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
use Pimcore\Bundle\StudioBackendBundle\Entity\ExecutionEngine\JobRunHidden;

/**
 * @internal
 */
final class Version20250616120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hide already existing studio job runs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS ' . JobRunHidden::TABLE_NAME . ' (
                jobRunId INT UNSIGNED NOT NULL,
                PRIMARY KEY (jobRunId),
                CONSTRAINT fk_' . JobRunHidden::TABLE_NAME . '_jobRunIds
                    FOREIGN KEY (jobRunId)
                    REFERENCES generic_execution_engine_job_run (id)
                    ON DELETE CASCADE
            )
        ');

        $this->addSql('
            INSERT INTO ' . JobRunHidden::TABLE_NAME . ' (jobRunId)
            SELECT jr.id
            FROM generic_execution_engine_job_run jr
            LEFT JOIN ' . JobRunHidden::TABLE_NAME . " jrh ON jr.id = jrh.jobRunId
            WHERE jrh.jobRunId IS NULL
            AND jr.executionContext IN ('studio_stop_on_error', 'studio_continue_on_error')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ' . JobRunHidden::TABLE_NAME);
    }
}
