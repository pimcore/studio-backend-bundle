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
    private function getLocalizedValue(Column $column, ElementInterface $element): mixed
    {
        $getter = $this->getGetter($column->getKey());
        if (!$column->getLocale()) {
            return $element->$getter();
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
     * Whether an empty localized value may fall further back to {@see self::getDefaultLanguage()}
     * once Pimcore's own configured fallback chain ({@see \Pimcore\Tool::getFallbackLanguagesFor()},
     * already applied by the field getter itself when {@see self::doGetFallbackValues()} is true)
     * still left it empty. True by default: the interactive Studio grid intentionally shows the
     * class's default-language value rather than a blank cell as a last resort. A caller that needs
     * export-consistent semantics - the real, configured fallback chain only, never an unconditional
     * default-language jump - overrides this to return false; see
     * {@see \Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedColumnSourceFieldContextInterface}.
     */
    protected function allowDefaultLanguageFallback(): bool
    {
        return true;
    }
}
