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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
trait LocalizedValueTrait
{
    use LocalizedFieldTrait;

    private function getLocalizedValue(Column $column, ElementInterface $element): mixed
    {
        $getter = $this->getGetter($column->getKey());
        if (!$column->getLocale()) {
            return $this->getValueInDefaultReadLocale($element, $getter, $column);
        }

        $value = $element->$getter($column->getLocale());

        if ($this->isEmptyValue($value) && $this->doGetFallbackValues() && $this->allowDefaultLanguageFallback()) {
            $defaultLanguage = $this->getDefaultLanguage();
            if ($defaultLanguage !== null && $defaultLanguage !== $column->getLocale()) {
                $value = $element->$getter($defaultLanguage);
            }
        }

        return $value;
    }

    private function getLocalizedValueFromKey(string $key, ?string $locale, ElementInterface $element): mixed
    {
        $getter = $this->getGetter($key);
        if ($locale) {
            return $element->$getter($locale);
        }

        return $element->$getter();
    }

    /**
     * The language every read of a column has to use: the column locale or, for a localized field of a column
     * without locale ("Default"), the system default language. A bare getter call would resolve to the request
     * locale instead, which in the Studio API is the framework locale. Null means "no language involved".
     */
    private function getColumnReadLocale(Column $column, ElementInterface $element): ?string
    {
        if ($column->getLocale()) {
            return $column->getLocale();
        }

        return $this->isLocalizedClassField($element, $column->getKey()) ? $this->getDefaultLanguage() : null;
    }

    private function getValueInDefaultReadLocale(ElementInterface $element, string $getter, Column $column): mixed
    {
        $locale = $this->getColumnReadLocale($column, $element);

        return $locale === null ? $element->$getter() : $element->$getter($locale);
    }

    private function getGetter(string $key): string
    {
        return 'get' . ucfirst($key);
    }

    private function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    protected function doGetFallbackValues(): bool
    {
        return false;
    }

    protected function getDefaultLanguage(): ?string
    {
        return null;
    }

    /**
     * Whether an empty localized value may jump to {@see self::getDefaultLanguage()} after Pimcore's configured
     * fallback languages left it empty. True by default, so the grid shows something instead of a blank cell.
     */
    protected function allowDefaultLanguageFallback(): bool
    {
        return true;
    }
}
