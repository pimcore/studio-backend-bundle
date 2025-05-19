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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant;

/**
 * @internal
 */
enum Mercure: string
{
    case AUTHORIZATION_COOKIE_NAME = 'mercureAuthorization';
    case HOST_PLACEHOLDER = '<PIMCORE_SCHEMA_HOST>';
    case URL_PATH = 'path';
    case URL_HOST = 'host';
    case URL_SCHEME = 'scheme';
    case URL_SCHEME_HTTPS = 'https';
}
