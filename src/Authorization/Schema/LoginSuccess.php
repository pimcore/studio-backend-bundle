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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Token',
    description: 'Token Scheme for API',
    type: 'object'
)]
final readonly class LoginSuccess
{
    public function __construct(
        #[Property(description: 'Username', type: 'string', example: 'admin')]
        private string $username,
        #[Property(description: 'Roles', type: 'array', items: new Items(type: 'string', example: 'ROLE_PIMCORE_ADMIN'))]
        private array $roles,
    ) {
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
    public function getUsername(): string
    {
        return $this->username;
    }
}
