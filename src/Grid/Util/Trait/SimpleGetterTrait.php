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
        // System columns read system properties. A bare getter on a localized field would resolve to the request
        // locale, while the grid authorizes the system default language for it - so such a key yields no value.
        if ($element instanceof ElementInterface && $this->isLocalizedClassField($element, $column->getKey())) {
            return null;
        }

        $getter = $this->getGetter($column, $element);
        if (method_exists($element, $getter) === false) {
            return null;
        }

        return $element->$getter();
    }

    private function getGetter(Column $column, StudioElementInterface|ElementInterface $element): string
    {
        $key = $column->getKey();
        if ($column->getKey() == 'filename' &&  $element instanceof DataObject) {
            $key = 'key';
        }

        return 'get' . ucfirst($key);
    }
}
