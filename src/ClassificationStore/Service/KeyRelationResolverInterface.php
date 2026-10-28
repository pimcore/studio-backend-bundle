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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
interface KeyRelationResolverInterface
{
    /**
     * @return KeyGroupRelation[] keyed by keyId, in id ascending order
     */
    public function getByGroup(int $groupId): array;

    /**
     * @param int[] $groupIds
     *
     * @return array<int, KeyGroupRelation[]> keyed by groupId, one entry per requested group
     */
    public function getByGroups(array $groupIds): array;

    /**
     * @throws NotFoundException
     */
    public function getByGroupAndKey(int $groupId, int $keyId): KeyGroupRelation;

    public function clear(): void;
}
