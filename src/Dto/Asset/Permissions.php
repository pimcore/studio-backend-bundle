<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Dto\Asset;

class Permissions
{
    //TODO: remove or change default permissions
    public function __construct(
        private bool $list = true,
        private bool $view = true,
        private bool $publish = true,
        private bool $delete = true,
        private bool $rename = true,
        private bool $create = true,
        private bool $settings = true,
        private bool $versions = true,
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
