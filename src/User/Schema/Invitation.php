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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Invitation',
    description: 'Invitation parameters',
    required: ['invitationUrl'],
    type: 'object'
)]
final readonly class Invitation
{
    public function __construct(
        #[Property(
            description: 'URL the invitation link points to. Must belong to a configured domain.',
            type: 'string',
            example: 'https://example.com/pimcore-studio/reset-password/'
        )]
        private string $invitationUrl,
    ) {
    }

    public function getInvitationUrl(): string
    {
        return $this->invitationUrl;
    }
}
