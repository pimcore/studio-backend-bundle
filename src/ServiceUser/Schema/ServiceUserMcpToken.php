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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use SensitiveParameter;

/**
 * An MCP access token issued for a service user. The plain token is returned once; only its
 * hash is stored. No pre-response event is dispatched for it, so the token never reaches a
 * listener.
 *
 * @internal
 */
#[Schema(
    schema: 'ServiceUserMcpToken',
    title: 'Service User MCP Access Token',
    description: 'An MCP access token for a service user. The token is shown only in this response.',
    required: ['token', 'expiresAt'],
    type: 'object'
)]
final readonly class ServiceUserMcpToken
{
    public function __construct(
        #[Property(description: 'The bearer token, shown once', type: 'string', example: 'pmcp_<value>')]
        #[SensitiveParameter]
        private string $token,
        #[Property(description: 'Expiry as a Unix timestamp', type: 'integer', example: 1718757677)]
        private int $expiresAt,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }
}
