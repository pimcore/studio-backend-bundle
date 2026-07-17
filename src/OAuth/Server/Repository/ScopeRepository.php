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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;
use function in_array;

/**
 * @internal
 */
final class ScopeRepository implements ScopeRepositoryInterface
{
    private const array SUPPORTED = ['mcp:read', 'mcp:write'];

    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        return in_array($identifier, self::SUPPORTED, true) ? new ScopeEntity($identifier) : null;
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     *
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        // The delegation-ceiling logic (narrowing to what the user may delegate)
        // is added with the scope/step-up work; pass validated scopes through.
        return $scopes;
    }
}
