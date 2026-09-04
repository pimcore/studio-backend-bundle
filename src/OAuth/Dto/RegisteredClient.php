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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Dto;

/**
 * Result of a successful registration. `secret` is the one-time plaintext
 * client secret (confidential clients only); it is returned to the caller once
 * and never persisted in the clear.
 *
 * @internal
 */
final readonly class RegisteredClient
{
    /**
     * @param list<string> $redirectUris
     * @param list<string> $grantTypes
     * @param list<string> $scopes
     */
    public function __construct(
        public string $identifier,
        public string $name,
        public array $redirectUris,
        public array $grantTypes,
        public array $scopes,
        public string $tokenEndpointAuthMethod,
        public ?string $secret,
        public int $issuedAt,
    ) {
    }
}
