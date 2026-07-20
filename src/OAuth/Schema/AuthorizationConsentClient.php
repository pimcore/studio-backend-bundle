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
    required: ['identifier', 'name'],
    type: 'object',
)]
final readonly class AuthorizationConsentClient
{
    public function __construct(
        #[Property(description: 'Client identifier', type: 'string', example: 'studio-mcp')]
        private string $identifier,
        #[Property(description: 'Client display name', type: 'string', example: 'Studio MCP')]
        private string $name,
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
}
