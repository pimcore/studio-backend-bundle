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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ClassIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FolderIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseClassIdTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseFolderIdTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing as ObjectBrickListing;
use function array_key_exists;

/**
 * @internal
 */
final class ObjectBrickCollector implements ColumnCollectorInterface, ClassIdInterface, FolderIdInterface
{
    use UseClassIdTrait;
    use UseFolderIdTrait;

    /**
     * @var ColumnConfiguration[]
     */
    private array $configurations = [];

    public function __construct(
        private readonly ClassDefinitionServiceInterface $classDefinitionService,
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService
    ) {
    }

    public function getCollectorName(): string
    {
        return 'data-object-object-brick';
    }

    /**
     * @inheritdoc
     */
    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
        $objectBrickList = new ObjectBrickListing();
        $objectBrickList = $objectBrickList->load();

        $classDefinition = $this->classDefinitionService->getClassDefinition($this->getClassId());

        $filteredFieldDefinitions = $this->classDefinitionService->getFilteredFieldDefinitions(
            $this->getClassId(),
            $this->getFolderId()
        );

        foreach ($objectBrickList as $objectBrick) {

            if (empty($objectBrick->getClassDefinitions())) {
                continue;
            }

            if (!$this->usesClass($objectBrick, $classDefinition)) {
                continue;
            }

            $fieldName = $this->getUsedFieldName($objectBrick, $classDefinition);

            if (!$this->fieldNameExists($fieldName, $filteredFieldDefinitions)) {
                continue;
            }

            $this->buildColumnConfigurations($objectBrick);

        }

        return $this->configurations;
    }

    private function buildColumnConfigurations(ObjectBrickDefinition $objectBrick): void
    {
        $dataFields = $this->getDataFields($objectBrick->getLayoutDefinitions());

        foreach ($dataFields as $dataField) {
            $groupName = $objectBrick->getTitle() !== '' ? $objectBrick->getTitle() : $objectBrick->getKey();

            $this->configurations[] = $this->columnConfigurationService->buildColumnConfiguration(
                new ColumnFieldDefinition($dataField, $groupName, false)
            );
        }
    }

    public function getDataFields(Layout $layout): array
    {
        $dataFields = [];
        foreach ($layout->getChildren() as $child) {
            if ($child instanceof Layout) {
                $dataFields = [...$dataFields, ...$this->getDataFields($child)];
            }

            if ($child instanceof ClassDefinition\Data) {
                $dataFields = [...$dataFields, $child];
            }
        }

        return $dataFields;
    }

    private function usesClass(ObjectBrickDefinition $objectBrick, ClassDefinition $classDefinition): bool
    {
        foreach ($objectBrick->getClassDefinitions() as $usedClassDefinition) {
            if ($usedClassDefinition['classname'] === $classDefinition->getName()) {
                return true;
            }
        }

        return false;
    }

    private function getUsedFieldName(ObjectBrickDefinition $objectBrick, ClassDefinition $classDefinition): string
    {
        foreach ($objectBrick->getClassDefinitions() as $usedClassDefinition) {
            if ($usedClassDefinition['classname'] === $classDefinition->getName()) {
                return $usedClassDefinition['fieldname'];
            }
        }

        throw new InvalidArgumentException('Field not found');
    }

    private function fieldNameExists(string $fieldName, array $filteredFieldDefinitions): bool
    {
        if (empty($filteredFieldDefinitions)) {
            return true;
        }

        if (
            array_key_exists($fieldName, $filteredFieldDefinitions) &&
            $filteredFieldDefinitions[$fieldName] instanceof Objectbricks
        ) {
            return true;
        }

        return false;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_DATA_OBJECT,
        ];
    }
}
