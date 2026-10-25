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
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationException;
use function in_array;

/**
 * @internal
 */
final readonly class ValidationErrorHydrator implements ValidationErrorHydratorInterface
{
    private const VALUE_PARAMETER = 'value';

    public function hydrate(ValidationException $exception, ?array $viewableLanguages = null): array
    {
        $errors = [];
        // a plain ValidationException becomes a single error without field, path or translation key
        foreach (StructuredValidationException::from($exception)->getViolations() as $violation) {
            $path = [];
            foreach ($violation->getPath() as $segment) {
                $path[] = new ValidationErrorPath(
                    $segment->field,
                    $segment->title,
                    $segment->language,
                    $segment->index,
                    $segment->type,
                    $segment->typeTitle,
                );
            }

            $errors[] = new ValidationError(
                $violation->getFieldName(),
                $violation->getFieldTitle(),
                $path,
                // sub items of a plain exception (e.g. from a custom container) only exist in the aggregated message
                $violation->getSubItems() === [] ? $violation->getMessage() : $violation->getAggregatedMessage(),
                $violation->getTranslationKey(),
                $this->getParameters($violation->getTranslationParameters(), $path, $viewableLanguages),
            );
        }

        return $errors;
    }

    /**
     * @param array<string, scalar|null> $parameters
     * @param list<ValidationErrorPath> $path
     * @param list<string>|null $viewableLanguages
     *
     * @return array<string, scalar|null>
     */
    private function getParameters(array $parameters, array $path, ?array $viewableLanguages): array
    {
        if ($viewableLanguages === null) {
            return $parameters;
        }

        foreach ($path as $segment) {
            $language = $segment->getLanguage();
            if ($language !== null && !in_array($language, $viewableLanguages, true)) {
                // the entered value must not reveal what the user may not see
                unset($parameters[self::VALUE_PARAMETER]);

                break;
            }
        }

        return $parameters;
    }
}
