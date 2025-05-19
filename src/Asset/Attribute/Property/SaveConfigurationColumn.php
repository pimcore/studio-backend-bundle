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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;

/**
 * @internal
 */
final class SaveConfigurationColumn extends Property
{
    public function __construct()
    {
        parent::__construct(
            'columns',
            type: 'array',
            items: new Items(ref: ColumnSchema::class),
            nullable: false,
        );
    }
}
