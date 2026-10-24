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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Model\Element\ValidationException;

/**
 * @internal
 */
trait ElementSaveExceptionTrait
{
    /**
     * Turns an exception from saving an element into the API exception: a failed validation becomes a 422
     * with the structured validation errors, anything else a database error.
     *
     * @throws DatabaseException|FieldValidationFailedException
     */
    private function throwElementSaveException(Exception $exception): never
    {
        if ($exception instanceof ValidationException) {
            throw new FieldValidationFailedException($exception->getMessage(), previous: $exception);
        }

        throw new DatabaseException($exception->getMessage());
    }
}
