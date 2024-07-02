<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Model\Element\ElementInterface;

trait SimpleGetterTrait
{
    /**
     * @throws InvalidArgumentException
     */
    private function getValue(Column $columnDefinition, ElementInterface $element): mixed
    {
        $getter = $this->getGetter($columnDefinition);
        if (method_exists($element, $getter) === false) {
            throw new InvalidArgumentException(
                'Method ' . $getter . ' does not exist on ' . get_class($element)
            );
        }

        return $element->$getter();
    }

    private function getGetter(Column $columnDefinition): string
    {
        return 'get' . ucfirst($columnDefinition->getKey());
    }
}
