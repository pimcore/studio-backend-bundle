<?php

namespace Pimcore\Bundle\StudioApiBundle\Config\OpenApi;

use OpenApi\Attributes\Tag;

#[Tag(name: Tags::Assets->name, description: 'Asset operations to get/update/create/delete assets')]
#[Tag(name: Tags::Authorization->name, description: 'Login via username and password to get a token or refresh the token')]
#[Tag(name: Tags::DataObjects->name, description: 'DataObject operations to get/update/create/delete data objects')]
#[Tag(name: Tags::Translation->name, description: 'Get translations either for a single key or multiple keys')]
enum Tags
{
    case Assets;
    case Authorization;
    case DataObjects;
    case Translation;
}
