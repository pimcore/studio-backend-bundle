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

namespace Pimcore\Bundle\StudioApiBundle\Config\OpenApi;

use OpenApi\Attributes\SecurityScheme;

#[SecurityScheme(
    securityScheme: 'auth_token',
    type: 'http',
    description: 'Bearer token for authentication',
    name: 'auth_token',
    scheme: 'bearer'
)]
final class Security
{
}
