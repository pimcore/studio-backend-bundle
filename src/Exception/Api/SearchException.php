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
use function sprintf;

/**
 * @internal
 */
final class SearchException extends AbstractApiException
{
    public function __construct(string $type, ?Throwable $previous = null)
    {
        parent::__construct(
            HttpResponseCodes::BAD_REQUEST->value,
            sprintf('Search for %s failed', $type),
            previous: $previous
        );
    }
}
