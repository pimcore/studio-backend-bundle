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

use OpenApi\Attributes\Info;
use OpenApi\Attributes\License;

/**
 * @internal
 * This class exists to document the public api
 */
#[Info(
    version: '0.0.1',
    description: 'API for Studio generated by OpenApi Generator via zircote/swagger-php',
    title: 'Studio API'
)]
#[License(
    name: 'GNU General Public License version 3 & Pimcore Commercial License',
    identifier: 'GPLv3 & PCL',
    url: 'https://www.pimcore.org/license'
)]

final class Spec
{
    private function __construct()
    {
    }
}
