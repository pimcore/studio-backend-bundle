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

namespace Pimcore\Bundle\StudioApiBundle\Dto\Asset;

class Permissions
{
    //TODO: remove or change default permissions
    public function __construct(
        private readonly bool $list = true,
        private readonly bool $view = true,
        private readonly bool $publish = true,
        private readonly bool $delete = true,
        private readonly bool $rename = true,
        private readonly bool $create = true,
        private readonly bool $settings = true,
        private readonly bool $versions = true,
        private readonly bool $properties = true
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
