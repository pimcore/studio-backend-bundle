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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'AuthorizationConsentClient',
    title: 'OAuth Consent Client',
    required: ['identifier', 'name', 'verified'],
    type: 'object',
)]
final readonly class AuthorizationConsentClient
{
    public function __construct(
        #[Property(description: 'Client identifier', type: 'string', example: 'studio-mcp')]
        private string $identifier,
        #[Property(description: 'Client display name (self-chosen; not authoritative)', type: 'string', example: 'Studio MCP')]
        private string $name,
        #[Property(
            description: 'Host (with port) of the redirect URI the authorization code will be sent to. '
                . 'The trustworthy signal of where access is granted.',
            type: 'string',
            example: 'localhost:6274',
            nullable: true,
        )]
        private ?string $redirectHost,
        #[Property(
            description: 'True if the client was pre-registered by an administrator; false for '
                . 'self-registered (DCR) or URL-identified (CIMD) clients, which should be shown as unverified.',
            type: 'boolean',
            example: false,
        )]
        private bool $verified,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRedirectHost(): ?string
    {
        return $this->redirectHost;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }
}
