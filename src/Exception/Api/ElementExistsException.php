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
use function sprintf;

/**
 * @internal
 */
final class ElementExistsException extends AbstractApiException
{
    public function __construct(?string $error = null, string $errorKey = HttpResponseErrorKeys::ELEMENT_EXISTS->value)
    {
        $message = sprintf(
            'Failed to create new element: %s',
            $error ?? 'Unknown error'
        );

        parent::__construct(
            HttpResponseCodes::CONFLICT->value,
            $message,
            errorKey: $errorKey
        );
    }
}
