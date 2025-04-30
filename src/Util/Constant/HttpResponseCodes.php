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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

enum HttpResponseCodes: int
{
    case SUCCESS = 200;
    case CREATED = 201;
    case NOT_COMPLETED = 202;
    case MULTI_STATUS = 207;
    case REDIRECT = 302;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case CONFLICT = 409;
    case MAX_FILE_SIZE_EXCEEDED = 413;
    case UNSUPPORTED_MEDIA_TYPE = 415;
    case UNPROCESSABLE_CONTENT = 422;
    case TOO_MANY_REQUESTS = 429;
    case INTERNAL_SERVER_ERROR = 500;
}
