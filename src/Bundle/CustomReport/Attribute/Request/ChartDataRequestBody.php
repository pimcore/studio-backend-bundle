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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Property\FilterProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ChartDataRequestBody extends RequestBody
{
    public function __construct(array $additionalProperties = [])
    {
        $properties = [
            new UpdateStringProperty('name'),
            new FilterProperty(),
            new Property(
                property: 'sortBy',
                description: 'Sort by field.',
                type: 'string',
                example: 'id'
            ),
            new Property(
                property: 'sortOrder',
                description: 'Sort order (ASC or DESC).',
                type: 'string',
                example: 'DESC'
            ),
            new Property(
                property: 'page',
                description: 'Page of the report data',
                type: 'integer',
                minimum: 1,
                example: 1
            ),
            new Property(
                property: 'pageSize',
                description: 'Page size of the report data',
                type: 'integer',
                minimum: 1,
                example: '50'
            ),
        ];

        if (!empty($additionalProperties)) {
            $properties = array_merge($properties, $additionalProperties);
        }

        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['name'],
                properties: $properties,
                type: 'object',
            ),
        );
    }
}
