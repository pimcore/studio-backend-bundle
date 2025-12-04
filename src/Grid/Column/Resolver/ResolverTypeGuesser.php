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
    private array $typeCache = [];

    public function __construct(
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly SecurityServiceInterface $securityService,
    ) {
    }

    public function guessType(string $key, string $classId, ?UserInterface $user = null): string
    {
        if ($user === null) {
            $user = $this->securityService->getCurrentUser();
        }

        $columnConfigurations = $this->getColumnConfigurations($classId, $user);

        return $this->findType($key, $classId, $columnConfigurations);
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

    /**
     * @param ColumnConfiguration[] $columnConfigurations
     */
    private function findType(string $key, string $classId, array $columnConfigurations): string
    {
        $cacheName = $classId .'_'. $key;

        if (isset($this->typeCache[$cacheName])) {
            return $this->typeCache[$cacheName];
        }

        foreach ($columnConfigurations as $columnConfiguration) {
            if ($columnConfiguration->getKey() === $key) {
                $this->typeCache[$cacheName] = $columnConfiguration->getType();

                return $columnConfiguration->getType();
            }
        }

        throw new NotFoundException('key', $key, 'Column Key');
    }
}
