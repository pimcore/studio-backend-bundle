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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class GdprSearchResultProperty extends Property
{
    public function __construct()
    {
        parent::__construct(
            property: 'items',
            description: 'List of search results, grouped by provider',
            type: 'array',
            items: new Items(
                required: ['providerKey', 'results'],
                properties: [
                    new Property(
                        property: 'providerKey',
                        description: 'The key of the provider these results came from',
                        type: 'string',
                        example: 'data_objects'
                    ),
                    new Property(
                        property: 'results',
                        description: 'The list of results found by this provider',
                        type: 'array',
                        items: new Items(type: 'object', example: '{"id": 1, "path": "/data/customer/1"}')
                    ),
                    new Property(
                        property: 'additionalAttributes',
                        description: 'Additional attributes for the search result',
                        type: 'object',
                        nullable: true
                    ),
                ]
            )
        );
    }
}
