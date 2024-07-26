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

namespace Pimcore\Bundle\StudioBackendBundle\Exception\Api;

use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use function sprintf;

/**
 * @internal
 */
final class ConsoleDependencyMissingException extends AbstractApiException
{
    public function __construct(string $executable, string $module = 'Pimcore')
    {
        parent::__construct(
            HttpResponseCodes::BAD_REQUEST->value,
            sprintf(
                'Please install the "%s" console executable on the server which is necessary for %s.',
                $executable,
                $module
            )
        );
    }
}
