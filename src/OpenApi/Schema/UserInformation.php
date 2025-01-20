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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'User Information',
    description: 'Information about the user',
    required: ['id', 'username', 'permissions', 'isAdmin'],
    type: 'object'
)]
final class UserInformation implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'User ID', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Username', type: 'string', example: 'admin')]
        private readonly string $username,
        #[Property(
            description: 'Permissions',
            type: 'array',
            items: new Items(type: 'string', example: 'clear_cache')
        )]
        private readonly array $permissions,
        #[Property(description: 'If user is an admin user', type: 'boolean', example: false)]
        private readonly bool $isAdmin,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }
}
