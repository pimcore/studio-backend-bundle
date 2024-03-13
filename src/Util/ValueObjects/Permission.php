<?php

namespace Pimcore\Bundle\StudioApiBundle\Util\ValueObjects;

final class Permission
{
    private readonly bool $list;
    private readonly bool $view;
    private readonly bool $publish;
    private readonly bool $delete;
    private readonly bool $rename;
    private readonly bool $create;
    private readonly bool $settings;
    private readonly bool $versions;
    private readonly bool $properties;

    public function __construct(
       array $userPermissions,
    ) {
        foreach($userPermissions as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value === 0;
            }
        }
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