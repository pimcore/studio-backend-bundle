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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Credentials',
    description: 'Credentials for authentication',
    required: ['username', 'password'],
    type: 'object'
)]
final readonly class Credentials
{
    public function __construct(
        #[Property(description: 'Username', type: 'string', example: 'shaquille.oatmeal')]
        private string $username,
        #[Property(description: 'Password', type: 'string', example: '*****')]
        private string $password,
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
