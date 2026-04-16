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

namespace Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use function sprintf;

/**
 * @internal
 */
final class LocalizedFieldService implements LocalizedFieldServiceInterface
{
    public function getFieldDefinition(Localizedfields $localizedfields, string $key): Data
    {
        $item = array_filter($localizedfields->getFieldDefinitions(), function (Data $field) use ($key) {
            return $field->getName() === $key;
        });
        $item = reset($item);

        if (!$item) {
            throw new ParseException(sprintf('Localized field definition "%s" does not exist', $key));
        }

        return $item;
    }
}
