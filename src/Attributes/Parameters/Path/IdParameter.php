<?php

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Path;

use Attribute;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class IdParameter extends PathParameter
{
    public function __construct(string $type = 'element')
    {
        parent::__construct(
            name: 'id',
            description: 'Id of the ' . $type,
            in: 'path',
            required: true,
            schema: new Schema(type: 'integer', example: 83),
        );
    }
}