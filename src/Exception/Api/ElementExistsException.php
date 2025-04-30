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
use function sprintf;

/**
 * @internal
 */
final class ElementExistsException extends AbstractApiException
{
    public function __construct(
        ?string $message = null,
        ?string $error = null,
        string $errorKey = HttpResponseErrorKeys::ELEMENT_EXISTS->value,
    ) {
        if ($message === null) {
            $message = sprintf(
                'Failed to create new element: %s',
                $error ?? 'Unknown error'
            );
        }

        parent::__construct(
            HttpResponseCodes::CONFLICT->value,
            $message,
            errorKey: $errorKey
        );
    }
}
