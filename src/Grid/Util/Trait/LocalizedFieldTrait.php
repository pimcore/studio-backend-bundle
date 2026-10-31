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

use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
trait LocalizedFieldTrait
{
    private function isLocalizedClassField(ElementInterface $element, string $key): bool
    {
        if (!$element instanceof Concrete) {
            return false;
        }

        $localizedFields = $element->getClass()->getFieldDefinition('localizedfields');

        return $localizedFields instanceof Localizedfields
            && $localizedFields->getFieldDefinition($key) !== null;
    }
}
