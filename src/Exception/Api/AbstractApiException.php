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
