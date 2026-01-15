<?php

declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Bundle\StudioBackendBundle\Entity\ExecutionEngine\JobRunHidden;

/**
 * @internal
 */
final class Version20260115100546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hide already existing studio job runs (fill the data)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO ' . JobRunHidden::TABLE_NAME . ' (jobRunId)
            SELECT jr.id
            FROM generic_execution_engine_job_run jr
            LEFT JOIN ' . JobRunHidden::TABLE_NAME . " jrh ON jr.id = jrh.jobRunId
            WHERE jrh.jobRunId IS NULL
            AND jr.executionContext IN ('studio_stop_on_error', 'studio_continue_on_error')
        ");
    }
    
}
