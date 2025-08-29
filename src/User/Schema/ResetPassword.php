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
    title: 'ResetPassword',
    description: 'Reset password parameters',
    required: ['username', 'resetPasswordUrl'],
    type: 'object'
)]
final readonly class ResetPassword
{
    public function __construct(
        #[Property(description: 'Username', type: 'string', example: 'shaquille.oatmeal')]
        private string $username,
        #[Property(description: 'Reset password URL', type: 'string', example: 'https://example.com/reset-password')]
        private string $resetPasswordUrl
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getResetPasswordUrl(): string
    {
        return $this->resetPasswordUrl;
    }
}
