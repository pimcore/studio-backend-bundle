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
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ResolverTypeGuesser implements ResolverTypeGuesserInterface
{
    /**
     * @var array<string, ColumnConfiguration[]>
     */
    private array $columnConfigurationCache = [];

    /**
     * @var array<string, ColumnConfiguration>
     */
    private array $columnConfigurationByKeyCache = [];

    public function __construct(
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly SecurityServiceInterface $securityService,
    ) {
    }

    public function guessType(string $key, string $classId, ?UserInterface $user = null): string
    {
        return $this->findColumnConfiguration($key, $classId, $user)->getType();
    }

    public function isLocalizable(string $key, string $classId, ?UserInterface $user = null): bool
    {
        return $this->findColumnConfiguration($key, $classId, $user)->isLocalizable();
    }

    private function findColumnConfiguration(string $key, string $classId, ?UserInterface $user = null): ColumnConfiguration
    {
        $cacheName = $classId . '_' . $key;

        if (isset($this->columnConfigurationByKeyCache[$cacheName])) {
            return $this->columnConfigurationByKeyCache[$cacheName];
        }

        if ($user === null) {
            $user = $this->securityService->getCurrentUser();
        }

        $columnConfigurations = $this->getColumnConfigurations($classId, $user);

        foreach ($columnConfigurations as $columnConfiguration) {
            if ($columnConfiguration->getKey() === $key) {
                $this->columnConfigurationByKeyCache[$cacheName] = $columnConfiguration;

                return $columnConfiguration;
            }
        }

        throw new NotFoundException('key', $key, 'Column Key');
    }

    /**
     * @return ColumnConfiguration[]
     */
    private function getColumnConfigurations(string $classId, UserInterface $user): array
    {
        if (isset($this->columnConfigurationCache[$classId])) {
            return $this->columnConfigurationCache[$classId];
        }

        $colConfiguration = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $classId,
            0,
            $user
        );

        $this->columnConfigurationCache[$classId] = $colConfiguration;

        return $colConfiguration;
    }
}
