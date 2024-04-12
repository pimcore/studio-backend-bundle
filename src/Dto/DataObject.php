<?php

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'DataObject',
    type: 'object'
)]
readonly class DataObject
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 83)]
        private int $id
    )
    {

    }

    public function getId(): int
    {
        return $this->id;
    }
}