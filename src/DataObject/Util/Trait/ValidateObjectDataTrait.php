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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\EncryptedField;

/**
 * @internal
 */
trait ValidateObjectDataTrait
{
    private function validateEncryptedField(Data $fieldDefinition, mixed $value): bool
    {
        return !($fieldDefinition instanceof EncryptedFieldDefinition && (!$value instanceof EncryptedField));
    }

    /**
     * @throws NotFoundException
     */
    private function getValidFieldValue(
        Concrete $object,
        string $key,
        ?string $language = null
    ): mixed {
        try {
            return $object->get($key, $language);
        } catch (Exception) {
            throw new NotFoundException(type: 'field', id: $key);
        }
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    private function getValidClass(
        ClassDefinitionResolverInterface $classDefinitionResolver,
        string $classId
    ): ClassDefinition {
        try {
            $class = $classDefinitionResolver->getById($classId);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage());
        }

        if (!$class) {
            throw new NotFoundException(ElementTypes::TYPE_CLASS_DEFINITION, $classId);
        }

        return $class;
    }
}
