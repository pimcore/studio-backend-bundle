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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Request;


use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdateElementProperty;

/**
 * @internal
 */
final readonly class UpdateElementProperties
{
    private array $properties;
    public function __construct(
       array $items
    ) {
        $this->properties = array_map(static function(array $propertyData) {
            return new UpdateElementProperty(
                $propertyData['key'],
                $propertyData['data'],
                $propertyData['type'],
                $propertyData['inheritable'],
            );
        }, $items);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
