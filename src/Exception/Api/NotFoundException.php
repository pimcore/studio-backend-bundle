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

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Throwable;
use function sprintf;

/**
 * @internal
 */
final class NotFoundException extends AbstractApiException
{
    public function __construct(string $type, int|string $id, string $parameter = 'ID', ?Throwable $previous = null)
    {
        parent::__construct(
            HttpResponseCodes::NOT_FOUND->value,
            sprintf('%s with %s: %s not found', ucfirst($type), $parameter, $id),
            previous: $previous,
            errorKey: HttpResponseErrorKeys::ELEMENT_NOT_FOUND->value
        );
    }
}
