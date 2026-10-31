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
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
trait LocalizedValueTrait
{
    private function getLocalizedValue(Column $column, ElementInterface $element): mixed
    {
        $getter = $this->getGetter($column->getKey());
        if (!$column->getLocale()) {
            return $this->getValueInDefaultReadLocale($element, $getter, $column->getKey());
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
     * A column without locale means "Default": a localized field is then read in the system default language.
     * A bare getter call would resolve to the request locale, which in the Studio API is the framework locale
     * and never the configured default language.
     */
    private function getValueInDefaultReadLocale(ElementInterface $element, string $getter, string $key): mixed
    {
        $defaultLanguage = $this->isLocalizedField($element, $key) ? $this->getDefaultLanguage() : null;

        return $defaultLanguage === null ? $element->$getter() : $element->$getter($defaultLanguage);
    }

    private function isLocalizedField(ElementInterface $element, string $key): bool
    {
        if (!$element instanceof Concrete) {
            return false;
        }

        $localizedFields = $element->getClass()->getFieldDefinition('localizedfields');

        return $localizedFields instanceof Localizedfields && $localizedFields->getFieldDefinition($key) !== null;
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
