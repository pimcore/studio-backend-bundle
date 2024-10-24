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

use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class UpdateIntegerProperty extends Property
{
    public function __construct(string $propertyName, int $minimum = 1)
    {
        parent::__construct(
            property: $propertyName,
            type: 'integer',
            minimum: $minimum,
            nullable: true
        );
    }
}
