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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\GroupInfoResolverTrait;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
final readonly class KeyGroupRelationHydrator implements KeyGroupRelationHydratorInterface
{
    use GroupInfoResolverTrait;

    public function hydrateKeyGroupRelationDetail(
        KeyGroupRelation $relation,
        ?string $keyName = null,
        ?string $keyDescription = null,
        ?string $groupName = null,
    ): KeyGroupRelationDetail {

        return new KeyGroupRelationDetail(
            $relation->getKeyId(),
            $relation->getGroupId(),
            $relation->getSorter(),
            $relation->isMandatory(),
            $keyName,
            $keyDescription,
            $groupName,
        );
    }
}
