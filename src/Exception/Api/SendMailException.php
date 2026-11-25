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
use Throwable;

/**
 * @internal
 */
final class SendMailException extends AbstractApiException
{
    /**
     * The message is returned to the caller of the unauthenticated reset password endpoint, so it must
     * not carry the reason of the failure. The reason stays available as the previous exception.
     */
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            HttpResponseCodes::INTERNAL_SERVER_ERROR->value,
            'Failed to send reset password mail',
            $previous,
        );
    }
}
