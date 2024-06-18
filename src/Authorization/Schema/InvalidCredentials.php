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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Invalid Credentials',
    description: 'Invalid credentials after login attempt',
    required: ['error'],
    type: 'object'
)]
final readonly class InvalidCredentials
{
    public function __construct(
        #[Property(description: 'Error', type: 'string', example: 'Invalid credentials')]
        private string $error,
    ) {
    }

    public function getError(): string
    {
        return $this->error;
    }
}
