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

namespace Pimcore\Bundle\StudioApiBundle\Response\Asset;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Permissions',
    type: 'object'
)]
final readonly class Permissions
{
    public function __construct(
        #[Property(description: 'List', type: 'bool', example: true)]
        private bool $list = true,
        #[Property(description: 'View', type: 'bool', example: true)]
        private bool $view = true,
        #[Property(description: 'Publish', type: 'bool', example: true)]
        private bool $publish = true,
        #[Property(description: 'Delete', type: 'bool', example: true)]
        private bool $delete = true,
        #[Property(description: 'Rename', type: 'bool', example: true)]
        private bool $rename = true,
        #[Property(description: 'Create', type: 'bool', example: true)]
        private bool $create = true,
        #[Property(description: 'Settings', type: 'bool', example: true)]
        private bool $settings = true,
        #[Property(description: 'Versions', type: 'bool', example: true)]
        private bool $versions = true,
        #[Property(description: 'Properties', type: 'bool', example: true)]
        private bool $properties = true
    ) {
    }

    public function isList(): bool
    {
        return $this->list;
    }

    public function isView(): bool
    {
        return $this->view;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }

    public function isDelete(): bool
    {
        return $this->delete;
    }

    public function isRename(): bool
    {
        return $this->rename;
    }

    public function isCreate(): bool
    {
        return $this->create;
    }

    public function isSettings(): bool
    {
        return $this->settings;
    }

    public function isVersions(): bool
    {
        return $this->versions;
    }

    public function isProperties(): bool
    {
        return $this->properties;
    }
}
