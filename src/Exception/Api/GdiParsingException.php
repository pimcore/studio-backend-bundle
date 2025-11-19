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
use Throwable;

/**
 * @internal
 */
final class GdiParsingException extends AbstractApiException
{
    public function __construct(
        string $message,
        private readonly int $position,
        private readonly string $expected,
        private readonly string $query,
        private readonly string $found,
        private readonly string|int|null $token = null,
        ?Throwable $previous = null,
        string $errorKey = HttpResponseErrorKeys::GDI_PARSING_EXCEPTION->value
    ) {
        parent::__construct(
            statusCode: HttpResponseCodes::UNPROCESSABLE_CONTENT->value,
            message: $message,
            previous: $previous,
            errorKey: $errorKey
        );
    }

    public function getToken(): string|int|null
    {
        return $this->token;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getExpected(): string
    {
        return $this->expected;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFound(): string
    {
        return $this->found;
    }
}
