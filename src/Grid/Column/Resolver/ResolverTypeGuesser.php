<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final class ResolverTypeGuesser implements ResolverTypeGuesserInterface
{
    /**
     * Summary of columnConfigurationCache
     *
     * @var array<string, ColumnConfiguration[]>
     */
    private array $columnConfigurationCache = [];

    /**
     * Summary of columnConfigurationCache
     *
     * @var string[]
     */
    private array $typeChache = [];

    public function __construct(
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly SecurityServiceInterface $securityService,
    ) {
    }

    public function guessType(string $key, string $classId): string
    {

        $columnConfigurations = $this->getCollumnConfigurations($classId);

        return $this->findType($key, $classId, $columnConfigurations);
    }

    /**
     * @return ColumnConfiguration[]
     */
    private function getCollumnConfigurations(string $classId): array
    {
        if (isset($this->columnConfigurationCache[$classId])) {
            return $this->columnConfigurationCache[$classId];
        }

        $colConfiguration = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $classId,
            0,
            $this->securityService->getCurrentUser()
        );

        $this->columnConfigurationCache[$classId] = $colConfiguration;

        return $colConfiguration;
    }

    /**
     * @param ColumnConfiguration[] $columnConfigurations
     */
    private function findType(string $key, string $classId, array $columnConfigurations): string
    {
        $chacheName = $classId .'_'. $key;

        if (isset($this->typeChache[$chacheName])) {
            return $this->typeChache[$chacheName];
        }

        foreach ($columnConfigurations as $columnConfiguration) {
            if ($columnConfiguration->getKey() === $key) {
                $this->typeChache[$chacheName] = $columnConfiguration->getType();

                return $columnConfiguration->getType();
            }
        }

        throw new NotFoundException('key', $key, 'Column Key');
    }
}
