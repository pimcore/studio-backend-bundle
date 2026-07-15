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
use Pimcore\Bundle\StudioBackendBundle\Entity\Asset\FolderPreviewSetting;

/**
 * Persist the asset folder preview image display size per user and folder so the choice survives
 * logout/login instead of only living in the browser session.
 *
 * @internal
 */
final class Version20260715120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table to persist asset folder preview image size per user and folder';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(FolderPreviewSetting::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(FolderPreviewSetting::TABLE_NAME);
        $table->addColumn('user', 'integer', ['notnull' => true, 'unsigned' => true]);
        $table->addColumn('assetFolderId', 'integer', ['notnull' => true, 'unsigned' => true]);
        $table->addColumn('imageSize', 'string', ['notnull' => true, 'length' => 10]);
        $table->setPrimaryKey(['user', 'assetFolderId'], 'pk_' . FolderPreviewSetting::TABLE_NAME);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(FolderPreviewSetting::TABLE_NAME)) {
            $schema->dropTable(FolderPreviewSetting::TABLE_NAME);
        }
    }
}
