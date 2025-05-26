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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Property;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class FilterProperty extends Property
{
    public function __construct(
        int $pageExample = 1,
        int $pageSizeExample = 50,
        bool $includeDescendantsExample = false,
        string $columnFiltersExample = '[{"key":"name","type":"metadata.object","filterValue":1}]',
        string $sortFilterExample = '{"key":"id","direction":"ASC"}'
    ) {
        parent::__construct(
            property: 'filters',
            properties: [
                new Property(
                    property: 'page',
                    type: 'integer',
                    example: $pageExample
                ),
                new Property(
                    property: 'pageSize',
                    type: 'integer',
                    example: $pageSizeExample
                ),
                new Property(
                    property: 'includeDescendants',
                    type: 'boolean',
                    example: $includeDescendantsExample
                ),
                new Property(
                    property: 'columnFilters',
                    type: 'object',
                    example: $columnFiltersExample,
                ),
                new Property(
                    property: 'sortFilter',
                    type: 'object',
                    example: $sortFilterExample
                ),
            ],
            type: 'object'
        );
    }
}
