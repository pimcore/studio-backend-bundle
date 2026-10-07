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
 * The location the browser must be sent to after completing an authorization —
 * the client redirect URI with the code, state and issuer.
 *
 * @internal
 */
#[Schema(
    schema: 'AuthorizationRedirect',
    title: 'OAuth Authorization Redirect',
    required: ['location'],
    type: 'object',
)]
final readonly class AuthorizationRedirect
{
    public function __construct(
        #[Property(
            description: 'Absolute URL to redirect the browser to',
            type: 'string',
            example: 'https://host/callback?code=def502...&state=xyz&iss=https%3A%2F%2Fhost',
        )]
        private string $location,
    ) {
    }

    public function getLocation(): string
    {
        return $this->location;
    }
}
