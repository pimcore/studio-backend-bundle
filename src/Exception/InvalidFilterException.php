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

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;

/**
 * @internal
 */
final class InvalidFilterException extends AbstractApiException
{
    public function __construct(string $filter)
    {
        parent::__construct(
            HttpResponseCodes::BAD_REQUEST->value,
            sprintf('Invalid filter: %s', $filter)
        );
    }
}
