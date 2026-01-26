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

namespace Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

/**
 * @internal
 */
final class ObjectBrickService implements ObjectBrickServiceInterface
{
    public function __construct(
        private readonly DefinitionResolverInterface $objectBrickdefinitionResolver,
    ) {
    }

    /**
     * @return ColumnFieldDefinition[]
     */
    public function getDataFields(Localizedfields|Layout $layout, bool $localized = false): array
    {
        $dataFields = [];
        foreach ($layout->getChildren() as $child) {
            if ($child instanceof Layout) {
                $dataFields = [...$dataFields, ...$this->getDataFields($child, $localized)];
            }

            if ($child instanceof Localizedfields) {
                $dataFields = [...$dataFields, ...$this->getDataFields($child, true)];
                continue;
            }

            if ($child instanceof Data) {
                $dataFields = [...$dataFields, new ColumnFieldDefinition($child, [], $localized),];
            }
        }

        return $dataFields;
    }

    public function findObjectBrickField(string $name, string $field): Data
    {
        $objectBrickDefinition = $this->objectBrickdefinitionResolver->getByKey($name);

        $fieldDefinition = $this->getDataFields($objectBrickDefinition->getLayoutDefinitions());

        foreach ($fieldDefinition as $dataField) {
            if ($dataField->getFieldDefinition()->getName() === $field) {
                return $dataField->getFieldDefinition();
            }
        }

        throw new NotFoundException('Object brick', $field, 'field');
    }
}
