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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Group;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;

/**
 * @internal
 */
final class GroupHydrator implements GroupHydratorInterface
{
    public function hydrate(GroupConfig $data): Group
    {
        return new Group(
            id: $data->getId(),
            name: $data->getName(),
            description: $data->getDescription()
        );
    }
}
