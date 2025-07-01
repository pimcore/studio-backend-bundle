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
use function sprintf;

/**
 * @internal
 */
final class InvalidDataTypeException extends AbstractApiException
{
    public function __construct(string $type, string $actualType, string $key = 'FieldDefinition')
    {
        parent::__construct(
            HttpResponseCodes::UNPROCESSABLE_CONTENT->value,
            sprintf(
                'Invalid %s type: should be %s and was %s',
                $key,
                $type,
                $actualType
            )
        );
    }
}
