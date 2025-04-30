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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait FieldDefinitionTrait
{
    /**
     * @throws Exception
     * @throws NotFoundException
     */
    private function getFieldDefinition(string $field, ClassDefinition $classDefinition): Data
    {
        $fieldDefinition = $classDefinition->getFieldDefinition($field);

        if (!$fieldDefinition instanceof Data) {
            throw new NotFoundException('Field definition', $field);
        }

        return $fieldDefinition;
    }
}
