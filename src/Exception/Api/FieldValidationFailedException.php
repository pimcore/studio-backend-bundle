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

/**
 * @internal
 */
final class FieldValidationFailedException extends AbstractApiException
{
    public function __construct(
        string $message,
        string $errorKey = HttpResponseErrorKeys::ELEMENT_VALIDATION_FAILED->value,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            HttpResponseCodes::UNPROCESSABLE_CONTENT->value,
            $message,
            previous: $previous,
            errorKey: $errorKey,
        );
    }
}
