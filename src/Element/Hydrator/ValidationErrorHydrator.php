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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ValidationError;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ValidationErrorPath;
use Pimcore\Model\Element\ValidationException;

/**
 * @internal
 */
final readonly class ValidationErrorHydrator implements ValidationErrorHydratorInterface
{
    public function hydrate(ValidationException $exception): array
    {
        $errors = [];
        foreach ($exception->getViolations() as $violation) {
            $path = [];
            foreach ($violation->getPath() as $segment) {
                $path[] = new ValidationErrorPath(
                    $segment->field,
                    $segment->title,
                    $segment->language,
                    $segment->index,
                    $segment->type,
                );
            }

            $errors[] = new ValidationError(
                $violation->getFieldName(),
                $violation->getFieldTitle(),
                $path,
                $violation->getMessage(),
                $violation->getTranslationKey(),
                $violation->getTranslationParameters(),
            );
        }

        return $errors;
    }
}
