<?php

namespace Pimcore\Bundle\StudioApiBundle\Config\OpenApi;

use OpenApi\Attributes\Tag;

#[Tag(name: 'Translation', description: 'Get translations either for a single key or multiple keys')]
#[Tag(name: 'Authorization', description: 'Login via username and password to get a token or refresh the token')]
final class Tags
{

}