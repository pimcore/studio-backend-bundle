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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Symfony\Contracts\Service\ResetInterface;
use function array_diff;
use function array_fill_keys;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;

/**
 * Loads the key relations of a group once per request and serves every later read of the same
 * group from memory, so the grid filters, the column resolver and the layout endpoints that
 * all ask for the same group issue one query between them instead of one each.
 *
 * Reads hand out clones. The configuration write path mutates relation objects (sorter,
 * mandatory), so a shared instance would let one consumer change what every other consumer
 * sees for the rest of the request.
 *
 * The memo is dropped by {@see clear()}, which the configuration services call after every
 * write, and by {@see reset()}: implementing ResetInterface makes autoconfiguration tag the
 * service `kernel.reset`, so a long-running worker drops the memo between messages instead of
 * serving the relations it saw first.
 *
 * @internal
 */
final class KeyRelationResolver implements KeyRelationResolverInterface, ResetInterface
{
    /**
     * @var array<int, KeyGroupRelation[]> keyed by groupId, each entry keyed by keyId
     */
    private array $memo = [];

    public function __construct(
        private readonly KeyGroupRelationRepositoryInterface $keyGroupRelationRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getByGroup(int $groupId): array
    {
        $this->memoise([$groupId]);

        return $this->copyAll($this->memo[$groupId]);
    }

    /**
     * {@inheritdoc}
     */
    public function getByGroups(array $groupIds): array
    {
        $this->memoise($groupIds);

        $relations = [];
        foreach ($groupIds as $groupId) {
            $relations[$groupId] = $this->copyAll($this->memo[$groupId]);
        }

        return $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function getByGroupAndKey(int $groupId, int $keyId): KeyGroupRelation
    {
        $this->memoise([$groupId]);

        if (!isset($this->memo[$groupId][$keyId])) {
            throw new NotFoundException('KeyGroupRelation', $keyId);
        }

        return clone $this->memo[$groupId][$keyId];
    }

    public function clear(): void
    {
        $this->memo = [];
    }

    public function reset(): void
    {
        $this->clear();
    }

    /**
     * @param int[] $groupIds
     */
    private function memoise(array $groupIds): void
    {
        $missing = array_values(array_unique(array_diff($groupIds, array_keys($this->memo))));

        if ($missing === []) {
            return;
        }

        // an entry per requested group, so a group without relations is memoised as empty
        $loaded = array_fill_keys($missing, []);

        foreach ($this->keyGroupRelationRepository->getByGroupIds($missing) as $relation) {
            $loaded[$relation->getGroupId()][$relation->getKeyId()] = $relation;
        }

        // committed only once the load succeeded, so a failed load memoises nothing
        $this->memo += $loaded;
    }

    /**
     * @param KeyGroupRelation[] $relations
     *
     * @return KeyGroupRelation[]
     */
    private function copyAll(array $relations): array
    {
        return array_map(
            static fn (KeyGroupRelation $relation): KeyGroupRelation => clone $relation,
            $relations
        );
    }
}
