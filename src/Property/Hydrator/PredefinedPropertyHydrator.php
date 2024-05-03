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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Model\Property\Predefined;

/**
 * @internal
 */
final readonly class PredefinedPropertyHydrator implements PredefinedPropertyHydratorInterface
{
    public function hydrate(Predefined $property): PredefinedProperty
    {
        return new PredefinedProperty(
            $property->getId(),
            $property->getName(),
            $property->getDescription(),
            $property->getKey(),
            $property->getType(),
            $property->getConfig(),
            $property->getInheritable(),
            $property->getCreationDate(),
            $property->getModificationDate()
        );
    }
}