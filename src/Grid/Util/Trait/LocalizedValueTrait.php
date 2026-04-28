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

        if ($this->isEmptyValue($value) && $this->doGetFallbackValues()) {
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
}
