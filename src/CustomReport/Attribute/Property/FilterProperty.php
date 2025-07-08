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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Attribute\Property;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class FilterProperty extends Property
{
    public function __construct()
    {
        parent::__construct(
            property: 'filters',
            properties: [
                new Property(
                    property: 'columnFilters',
                    type: 'object',
                    example: '[{"property": "count(*)", "type":"numeric","operator":"lt","value":"15"}]',
                ),
                new Property(
                    property: 'drillDownFilters',
                    type: 'object',
                    example: '{"field":"value","anotherField":"anotherValue"}'
                ),
            ],
            type: 'object'
        );
    }
}
