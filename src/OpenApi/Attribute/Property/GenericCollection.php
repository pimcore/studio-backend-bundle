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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class GenericCollection extends Property
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class)
    {
        parent::__construct(
            'items',
            title: 'items',
            type: 'array',
            items: new Items(ref: $class)
        );
    }
}
