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
    private const HIDDEN_MESSAGE = 'The value is not valid.';

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

            // message and parameters can carry the entered value: for a language the user may not view,
            // only the generic message and the translation key are returned
            $hidden = $this->isHidden($path, $viewableLanguages);

            $errors[] = new ValidationError(
                $violation->getFieldName(),
                $violation->getFieldTitle(),
                $path,
                $hidden ? self::HIDDEN_MESSAGE : $this->getMessage($violation),
                $violation->getTranslationKey(),
                $hidden ? [] : $violation->getTranslationParameters(),
            );
        }

        return $errors;
    }

    /**
     * Sub items of a plain exception (e.g. from a custom container) only exist in the aggregated message
     */
    private function getMessage(StructuredValidationException $violation): string
    {
        return $violation->getSubItems() === [] ? $violation->getMessage() : $violation->getAggregatedMessage();
    }

    /**
     * @param list<ValidationErrorPath> $path
     * @param list<string>|null $viewableLanguages
     */
    private function isHidden(array $path, ?array $viewableLanguages): bool
    {
        if ($viewableLanguages === null) {
            return false;
        }

        foreach ($path as $segment) {
            $language = $segment->getLanguage();
            if ($language !== null && !in_array($language, $viewableLanguages, true)) {
                return true;
            }
        }

        return false;
    }
}
