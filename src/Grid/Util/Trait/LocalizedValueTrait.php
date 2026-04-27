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
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
trait LocalizedValueTrait
{
    private function getLocalizedValue(Column $column, ElementInterface $element): mixed
    {
        $getter = $this->getGetter($column->getKey());
        if ($column->getLocale()) {
            if ($column->getApplyFallbackLanguages()) {
                Localizedfield::setGetFallbackValues(true);
                $value = $element->$getter($column->getLocale());
                Localizedfield::setGetFallbackValues(false);
            } else {
                $value = $element->$getter($column->getLocale());
            }

            return $value;
        }

        return $element->$getter();
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
}
