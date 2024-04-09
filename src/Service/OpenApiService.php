<?php

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

namespace Pimcore\Bundle\StudioApiBundle\Service;

use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;

final class OpenApiService implements OpenApiServiceInterface
{
    private const PATH_PREFIX = __DIR__ . '/../../';

    private const PATHS = [
        self::PATH_PREFIX . 'src/Controller/Api',
        self::PATH_PREFIX . 'src/Dto/',
        self::PATH_PREFIX . 'src/Config/',
    ];

    public function getConfig(): OpenApi
    {
        return Generator::scan(self::PATHS);
    }
}
