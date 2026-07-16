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

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS ' . FolderPreviewSetting::TABLE_NAME . ' (
                user INT UNSIGNED NOT NULL,
                assetFolderId INT UNSIGNED NOT NULL,
                imageSize VARCHAR(10) NOT NULL,
                PRIMARY KEY (user, assetFolderId)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ' . FolderPreviewSetting::TABLE_NAME);
    }
}
