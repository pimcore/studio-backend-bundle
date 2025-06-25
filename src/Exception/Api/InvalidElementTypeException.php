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

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use function sprintf;

/**
 * @internal
 */
final class InvalidElementTypeException extends AbstractApiException
{
    public function __construct(string $subType, string $elementType = ElementTypes::TYPE_ELEMENT)
    {
        parent::__construct(
            HttpResponseCodes::UNPROCESSABLE_CONTENT->value,
            sprintf(
                'Invalid %s type: %s',
                $elementType,
                $subType
            )
        );
    }
}
