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
 * The user's decision on a pending authorization.
 *
 * @internal
 */
// `approved` is required and non-nullable on purpose, and the reason is not stylistic.
// This endpoint decides an allow or a deny, so a body that does not say which is a
// malformed request rather than a refusal. Binding it here makes Symfony answer 400 or
// 422 before the action runs; parsing it inside the action instead would read an
// unreadable body as a denial and hand the client an access_denied redirect it cannot
// tell from a real one. Kept out of the docblock so the rationale does not ship as the
// schema description in the public OpenAPI document.
#[Schema(
    title: 'ApproveAuthorization',
    required: ['approved'],
    type: 'object'
)]
final readonly class ApproveAuthorization
{
    public function __construct(
        #[Property(
            description: 'Whether the user approved the authorization',
            type: 'boolean',
            example: true,
        )]
        private bool $approved,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }
}
