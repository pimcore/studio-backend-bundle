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
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;

trait SimpleGetterTrait
{
    use LocalizedFieldTrait;

    private function getValue(Column $column, StudioElementInterface|ElementInterface $element): mixed
    {
        $property = $this->getProperty($column, $element);

        // System columns read system properties. A bare getter on a localized field would resolve to the request
        // locale, while the grid authorizes the system default language for it - so such a key yields no value.
        // The getter name is case-insensitive, so the key is matched against the localized fields the same way.
        if ($element instanceof ElementInterface && $this->isLocalizedClassFieldIgnoringCase($element, $property)) {
            return null;
        }

        $getter = 'get' . ucfirst($property);
        if (method_exists($element, $getter) === false) {
            return null;
        }

        return $element->$getter();
    }

    private function getProperty(Column $column, StudioElementInterface|ElementInterface $element): string
    {
        if ($column->getKey() === 'filename' && $element instanceof DataObject) {
            return 'key';
        }

        return $column->getKey();
    }
}
