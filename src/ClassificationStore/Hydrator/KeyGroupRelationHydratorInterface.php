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
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation as CoreKeyGroupRelation;

/**
 * @internal
 */
interface KeyGroupRelationHydratorInterface
{
    /**
     * `$keyLocalized` and `$keyConfig` are the key's own columns, which core's relation listing
     * selects and then drops: the caller reads them for the whole page and passes each relation's
     * own, so that hydrating a row costs no statement.
     */
    public function hydrate(
        CoreKeyGroupRelation $keyGroupRelation,
        GroupConfig $groupConfig,
        ?bool $keyLocalized = null,
        ?KeyConfig $keyConfig = null,
    ): KeyGroupRelation;
}
