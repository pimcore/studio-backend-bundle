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

/**
 * Summary of the MCP access tokens of a service user. Only a count and an expiry, never a token
 * or its hash.
 *
 * @internal
 */
#[Schema(
    schema: 'ServiceUserMcpTokens',
    title: 'Service User MCP Access Token Summary',
    description: 'How many MCP access tokens of a service user are still valid',
    required: ['active', 'latestExpiresAt'],
    type: 'object'
)]
final readonly class ServiceUserMcpTokens
{
    public function __construct(
        #[Property(description: 'Number of tokens that have not expired', type: 'integer', example: 1)]
        private int $active,
        #[Property(
            description: 'Latest expiry among the active tokens as a Unix timestamp, null without one',
            type: 'integer',
            example: 1718757677,
            nullable: true
        )]
        private ?int $latestExpiresAt,
    ) {
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function getLatestExpiresAt(): ?int
    {
        return $this->latestExpiresAt;
    }
}
