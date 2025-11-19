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

namespace Pimcore\Bundle\StudioBackendBundle\Exception\Api;

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;

/**
 * @internal
 */
final class ForbiddenException extends AbstractApiException
{
    public function __construct(
        string $message = 'Access Denied',
        string $errorKey = HttpResponseErrorKeys::GENERIC_ERROR->value
    ) {
        parent::__construct(
            statusCode: HttpResponseCodes::FORBIDDEN->value,
            message: $message,
            errorKey: $errorKey
        );
    }
}
