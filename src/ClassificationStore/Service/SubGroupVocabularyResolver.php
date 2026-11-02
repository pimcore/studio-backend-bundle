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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Reads the sub group vocabulary of a store once per request and serves every later read of the
 * same store from memory, so the collection layout endpoint - one group layout per group it
 * composes - resolves the sub groups of all of them with a single query instead of one per group.
 *
 * The memo is dropped by {@see clear()}, which the sub group configuration service calls after
 * every write, and by {@see reset()}: implementing ResetInterface makes autoconfiguration tag the
 * service `kernel.reset`, so a long-running worker drops the memo between messages instead of
 * serving the vocabulary it saw first.
 *
 * @internal
 */
final class SubGroupVocabularyResolver implements SubGroupVocabularyResolverInterface, ResetInterface
{
    /**
     * @var array<int, array<string, SubGroupConfig>> keyed by storeId, each entry keyed by identifier
     */
    private array $memo = [];

    public function __construct(
        private readonly SubGroupRepositoryInterface $subGroupConfigurationRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getByStore(int $storeId): array
    {
        if (isset($this->memo[$storeId])) {
            return $this->memo[$storeId];
        }

        $vocabulary = [];
        foreach ($this->subGroupConfigurationRepository->getByStoreId($storeId) as $subGroupConfig) {
            $identifier = $subGroupConfig->getIdentifier();
            if ($identifier === null) {
                continue;
            }

            $vocabulary[$identifier] = $subGroupConfig;
        }

        // committed only once the load succeeded, so a failed load memoises nothing
        $this->memo[$storeId] = $vocabulary;

        return $vocabulary;
    }

    public function clear(): void
    {
        $this->memo = [];
    }

    public function reset(): void
    {
        $this->clear();
    }
}
