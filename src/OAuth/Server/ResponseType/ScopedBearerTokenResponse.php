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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\ResponseType;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use SensitiveParameter;
use function array_map;
use function implode;

/**
 * Adds the `scope` response parameter, which league omits entirely.
 *
 * A token is downscoped to what the resource it names can process, so the granted set
 * regularly differs from what the client asked for. RFC 6749 section 5.1 requires the
 * effective scope to be reported whenever it does, and permits reporting it always;
 * always is what this does, because a response type cannot see the requested set.
 *
 * @internal
 */
final class ScopedBearerTokenResponse extends BearerTokenResponse
{
    /**
     * @return array<string, mixed>
     */
    protected function getExtraParams(
        #[SensitiveParameter]
        AccessTokenEntityInterface $accessToken
    ): array {
        $scopes = array_map(
            static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $accessToken->getScopes(),
        );

        // An empty `scope` string says nothing a missing key does not, and a client
        // comparing it against its request would read it as a scope named "".
        return $scopes === [] ? [] : ['scope' => implode(' ', $scopes)];
    }
}
