<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleInteger;

#[Attribute(Attribute::TARGET_METHOD)]
final class PathFormatterRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['objectId', 'targets', 'context'],
                properties: [
                    new SingleInteger('objectId'),
                    new Property(
                        property: 'targets',
                        type: 'object',
                        example: '{"object_10":{"id":10,"type":"object","label":"/Product
                        Data/Cars/jaguar/E-Type/coupé","path":"/Product Data/Cars/jaguar/E-Type/coupé",
                        "nicePathKey":"object_10"}}'
                    ),
                    new Property(
                        property: 'fieldName',
                        description: 'Fieldname for the PathFormatter. Given as Dot Notation',
                        type: 'string',
                        example: 'attributes.Bodywork.numberOfDoors'
                    ),
                ]
            )
        );
    }
}
