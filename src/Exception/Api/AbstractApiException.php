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

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

abstract class AbstractApiException extends HttpException
{
    public function __construct(
        int $statusCode,
        ?string $message = null,
        ?Throwable $previous = null,
        array $headers = [],
        ?int $code = 0,
        private readonly string $errorKey = HttpResponseErrorKeys::GENERIC_ERROR->value
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getErrorKey(): string
    {
        return $this->errorKey;
    }
}
