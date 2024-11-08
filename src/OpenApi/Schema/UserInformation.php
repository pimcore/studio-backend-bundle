<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'User Information',
    description: 'Information about the user',
    required: ['username', 'permissions', 'isAdmin'],
    type: 'object'
)]
final readonly class UserInformation
{
    public function __construct(
        #[Property(description: 'Username', type: 'string', example: 'admin')]
        private string $username,
        #[Property(
            description: 'Permissions',
            type: 'array',
            items: new Items(type: 'string', example: 'clear_cache')
        )]
        private array $permissions,
        #[Property(description: 'If user is an admin user', type: 'boolean', example: false)]
        private bool $isAdmin,
    ) {
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }
}
