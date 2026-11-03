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

    /**
     * PHP getters are case-insensitive while the class definition lookup is not, so a getter derived from a client
     * supplied key ("Name") can reach a localized field that isLocalizedClassField() does not report ("name").
     */
    private function isLocalizedClassFieldIgnoringCase(ElementInterface $element, string $key): bool
    {
        if (!$element instanceof Concrete) {
            return false;
        }

        $localizedFields = $element->getClass()->getFieldDefinition('localizedfields');
        if (!$localizedFields instanceof Localizedfields) {
            return false;
        }

        foreach (array_keys($localizedFields->getFieldDefinitions(['suppressEnrichment' => true])) as $name) {
            if (strcasecmp((string)$name, $key) === 0) {
                return true;
            }
        }

        return false;
    }
}
