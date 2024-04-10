<?php

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Response;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use Pimcore\Bundle\StudioApiBundle\Dto\Unauthorized;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class UnauthorizedResponse extends Response
{
    public function __construct()
    {
        parent::__construct(
            response: 401,
            description: 'Unauthorized',
            content: new JsonContent(ref: Unauthorized::class)
        );
    }
}