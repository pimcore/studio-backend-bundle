<?php

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Request;

final readonly class UpdateAsset
{
    public function __construct(
        private array $data
    )
    {
    }

    public function getData(): array
    {
        return $this->data;
    }
}