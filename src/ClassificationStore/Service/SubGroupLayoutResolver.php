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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use function array_values;

/**
 * @internal
 */
final readonly class SubGroupLayoutResolver implements SubGroupLayoutResolverInterface
{
    public function __construct(
        private SubGroupRepositoryInterface $subGroupConfigurationRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $keys, int $storeId): array
    {
        $identifiers = [];
        foreach ($keys as $key) {
            $identifier = $key->getSubGroup();
            if ($identifier === null) {
                continue;
            }

            // valued as well as keyed: array_keys() would hand back an all digit identifier as an int
            $identifiers[$identifier] = $identifier;
        }

        if (empty($identifiers)) {
            return [];
        }

        $configs = $this->subGroupConfigurationRepository->getByIdentifiers(
            $storeId,
            array_values($identifiers)
        );

        $subGroups = [];
        foreach ($identifiers as $identifier) {
            $config = $configs[$identifier] ?? null;
            if (!$config) {
                continue;
            }

            $subGroups[] = new SubGroupLayout(
                identifier: $identifier,
                name: $config->getName(),
                description: $config->getDescription(),
            );
        }

        return $subGroups;
    }
}
