<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
