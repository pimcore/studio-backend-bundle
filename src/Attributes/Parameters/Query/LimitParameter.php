<?php

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class LimitParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'limit',
            description: 'Number of items per page',
            in: 'query',
            required: true,
            schema: new Schema(type: 'integer', example: 10),
        );
    }
}