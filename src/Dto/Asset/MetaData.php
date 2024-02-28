<?php

namespace Pimcore\Bundle\StudioApiBundle\Dto\Asset;

use ApiPlatform\Metadata\ApiProperty;

class MetaData
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $language,
        private readonly string $type,
        private readonly mixed $data,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}