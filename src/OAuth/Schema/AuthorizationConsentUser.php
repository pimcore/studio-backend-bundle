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
    schema: 'AuthorizationConsentUser',
    title: 'OAuth Consent User',
    required: ['id', 'username'],
    type: 'object',
)]
final readonly class AuthorizationConsentUser
{
    public function __construct(
        #[Property(description: 'Pimcore user id the token will act as', type: 'integer', example: 22)]
        private int $id,
        #[Property(description: 'Pimcore username', type: 'string', example: 'mcp-admin')]
        private string $username,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
