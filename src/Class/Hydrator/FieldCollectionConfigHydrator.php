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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionConfig;
use Pimcore\Model\DataObject\Fieldcollection\Definition;

/**
 * @internal
 */
final readonly class FieldCollectionConfigHydrator implements FieldCollectionConfigHydratorInterface
{
    public function hydrate(Definition $definition): FieldCollectionConfig
    {
        return new FieldCollectionConfig(
            $definition->getKey(),
            $definition->getTitle()
        );
    }
}

