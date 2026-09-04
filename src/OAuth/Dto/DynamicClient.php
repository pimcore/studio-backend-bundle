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
 * A dynamically registered client as stored and looked up by the client
 * repository. `secretHash` is the SHA-256 of the issued secret for confidential
 * clients, null for public (PKCE) clients.
 *
 * @internal
 */
final readonly class DynamicClient
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
        public bool $confidential,
        public ?string $secretHash,
    ) {
    }
}
