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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation as CoreKeyGroupRelation;

/**
 * @internal
 */
final class KeyGroupRelationHydrator implements KeyGroupRelationHydratorInterface
{
    public function hydrate(CoreKeyGroupRelation $keyGroupRelation, GroupConfig $groupConfig): KeyGroupRelation
    {
        return new KeyGroupRelation(
            keyId: $keyGroupRelation->getKeyId(),
            groupId: $groupConfig->getId(),
            keyName: $keyGroupRelation->getName(),
            groupName: $groupConfig->getName(),
            keyDescription: $keyGroupRelation->getDescription(),
            groupDescription: $groupConfig->getDescription(),
        );
    }
}
