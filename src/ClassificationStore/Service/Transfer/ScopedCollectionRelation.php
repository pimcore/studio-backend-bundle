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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;

/**
 * A stored collection-group relation together with the identifiers of the collection and the group
 * it joins. The relation itself carries only the two row ids, which name nothing a file could be
 * matched by.
 *
 * @internal
 */
final readonly class ScopedCollectionRelation
{
    public function __construct(
        private CollectionGroupRelation $relation,
        private string $collectionIdentifier,
        private string $groupIdentifier,
    ) {
    }

    public function getRelation(): CollectionGroupRelation
    {
        return $this->relation;
    }

    public function getCollectionIdentifier(): string
    {
        return $this->collectionIdentifier;
    }

    public function getGroupIdentifier(): string
    {
        return $this->groupIdentifier;
    }
}
