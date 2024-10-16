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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant;

/**
 * @internal
 */
enum Mercure: string
{
    case AUTHORIZATION_COOKIE_NAME = 'mercureAuthorization';
    case URL_PATH = 'path';
    case URL_HOST = 'host';
    case URL_SCHEME = 'scheme';
    case URL_SCHEME_HTTPS = 'https';
}
