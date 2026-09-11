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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Registry;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use function in_array;

/**
 * @internal
 */
final class ScopeRegistry implements ScopeRegistryInterface
{
    /**
     * @var list<string>|null
     */
    private ?array $scopes = null;

    /**
     * @param iterable<ScopeProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function all(): array
    {
        if ($this->scopes !== null) {
            return $this->scopes;
        }

        $scopes = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->scopes() as $scope) {
                if ($scope !== '' && !in_array($scope, $scopes, true)) {
                    $scopes[] = $scope;
                }
            }
        }

        $this->scopes = $scopes;

        return $this->scopes;
    }

    public function has(string $scope): bool
    {
        return in_array($scope, $this->all(), true);
    }
}
