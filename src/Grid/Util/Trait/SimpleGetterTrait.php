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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Model\Element\ElementInterface;
use function get_class;

trait SimpleGetterTrait
{
    private function getValue(Column $column, StudioElementInterface|ElementInterface $element): mixed
    {
        $getter = $this->getGetter($column);
        if (method_exists($element, $getter) === false) {
            return null;
        }

        return $element->$getter();
    }

    private function getGetter(Column $column): string
    {
        return 'get' . ucfirst($column->getKey());
    }
}
