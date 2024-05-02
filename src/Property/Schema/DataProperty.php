<?php

namespace Pimcore\Bundle\StudioBackendBundle\Property\Schema;

use OpenApi\Attributes\Property;

final class DataProperty
{
    public function __construct(
        #[Property(description: 'name', type: 'string', example: 'Mister Proper')]
        private string $name,
        #[Property(description: 'data', type: 'string', example: '123')]
        private ?string $data,
        #[Property(description: 'type', type: 'string', example: 'document')]
        private string $type,
        #[Property(description: 'inheritable', type: 'boolean', example: false)]
        private bool $inheritable,
        #[Property(description: 'inherited', type: 'boolean', example: false)]
        private bool $inherited,
        #[Property(description: 'config', type: 'string', example: 'comma,separated,values')]
        private ?string $config,
        #[Property(description: 'predefinedName', type: 'string', example: 'name of the predefined property')]
        private ?string $predefinedName,
        #[Property(description: 'description', type: 'string', example: 'Description of the predefined property')]
        private ?string $description,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIsInheritable(): bool
    {
        return $this->inheritable;
    }

    public function getIsInherited(): bool
    {
        return $this->inherited;
    }
}