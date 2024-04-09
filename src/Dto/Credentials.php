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

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use OpenApi\Attributes\Schema;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
#[Schema(
    title: 'Credentials',
    description: 'Credentials for authentication',
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
