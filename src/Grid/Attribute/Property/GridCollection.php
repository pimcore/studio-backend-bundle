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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Property;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;

/**
 * @internal
 */
final class GridCollection extends Property
{
    public function __construct()
    {
        parent::__construct(
            property: 'items',
            required: ['id', 'columns', 'isLocked', 'permissions'],
            type: 'array',
            items: new Items(
                properties: [
                    new Property(
                        property: 'id',
                        type: 'integer',
                    ),
                    new Property(
                        property: 'columns',
                        type: 'array',
                        items: new Items(ref: ColumnData::class),
                    ),
                    new Property(
                        property: 'isLocked',
                        type: 'bool'
                    ),
                    new Property(
                        property: 'permissions',
                        ref: Permissions::class,
                        type: 'object'
                    ),

                ]
            )
        );
    }
}
