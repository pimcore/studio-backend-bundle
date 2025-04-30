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
use function sprintf;

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
