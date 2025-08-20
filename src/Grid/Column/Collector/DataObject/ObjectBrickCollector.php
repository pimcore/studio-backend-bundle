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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ClassIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FolderIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseClassIdTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseFolderIdTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseUserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseUserTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing as ObjectBrickListing;
use Psr\Log\LoggerInterface;
use function array_key_exists;

/**
 * @internal
 */
final class ObjectBrickCollector implements
    ColumnCollectorInterface,
    ClassIdInterface,
    FolderIdInterface,
    UseUserInterface
{
    use UseClassIdTrait;
    use UseFolderIdTrait;
    use UseUserTrait;

    /**
     * @var ColumnConfiguration[]
     */
    private array $configurations = [];

    public function __construct(
        private readonly ClassDefinitionServiceInterface $classDefinitionService,
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getCollectorName(): string
    {
        return 'data-object-object-brick';
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
        $objectBrickList = new ObjectBrickListing();
        $objectBrickList = $objectBrickList->load();

        $classDefinition = $this->classDefinitionService->getClassDefinition($this->getClassId());

        $filteredFieldDefinitions = $this->classDefinitionService->getFilteredFieldDefinitions(
            $this->getClassId(),
            $this->getFolderId(),
            $this->getUser()
        );

        foreach ($objectBrickList as $objectBrick) {

            if (empty($objectBrick->getClassDefinitions())) {
                continue;
            }

            if (!$this->usesClass($objectBrick, $classDefinition)) {
                continue;
            }

            $fieldNames = $this->getUsedFieldNames($objectBrick, $classDefinition);

            foreach ($fieldNames as $fieldName) {
                if (!$this->fieldNameExists($fieldName, $filteredFieldDefinitions)) {
                    continue;
                }

                $baseLayoutName = $this->getBaseLayoutName($fieldName, $classDefinition->getLayoutDefinitions());

                if (!$baseLayoutName) {
                    throw new InvalidArgumentException('Base layout name not found for field ' . $fieldName);
                }

                $this->buildColumnConfigurations($objectBrick, $fieldName, $baseLayoutName);
            }

        }

        return $this->configurations;
    }

    private function buildColumnConfigurations(
        ObjectBrickDefinition $objectBrick,
        string $fieldname,
        string $baseLayoutName
    ): void {
        $dataFields = $this->getDataFields($objectBrick->getLayoutDefinitions());

        foreach ($dataFields as $dataField) {
            $grouping = [
                $baseLayoutName,
                $fieldname,
                $objectBrick->getKey(),
            ];

            try {
                $this->configurations[] = $this->columnConfigurationService->buildDataObjectAdapterColumnConfiguration(
                    new ColumnFieldDefinition($dataField, [$grouping], false),
                    'dataobject.objectbrick',
                    $fieldname . '.'. $objectBrick->getKey() . '.'. $dataField->getName(),
                    [
                        'field' => $fieldname,
                        'objectBrick' => $objectBrick->getKey(),
                        'attribute' => $dataField->getName(),
                    ]
                );
            } catch (InvalidArgumentException $exception) {
                $this->logger->info($exception->getMessage());

                continue;
            }
        }
    }

    /**
     * @return Data[]
     */
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

    /**
     * @return string[]
     */
    private function getUsedFieldNames(ObjectBrickDefinition $objectBrick, ClassDefinition $classDefinition): array
    {
        $fieldNames = [];
        foreach ($objectBrick->getClassDefinitions() as $usedClassDefinition) {
            if ($usedClassDefinition['classname'] === $classDefinition->getName()) {
                $fieldNames[] = $usedClassDefinition['fieldname'];
            }
        }

        return $fieldNames;
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

    private function getBaseLayoutName(string $fieldname, Layout $layout, ?string $baseLayoutName = null): ?string
    {
        foreach ($layout->getChildren() as $child) {
            if ($child instanceof Layout) {
                if (!$baseLayoutName) {
                    $baseLayoutName = $this->getBaseLayoutName($fieldname, $child, $child->getTitle());
                }

                $baseLayoutName = $this->getBaseLayoutName($fieldname, $child, $baseLayoutName);

                if ($baseLayoutName === null) {
                    continue;
                }

                return $baseLayoutName;
            }

            if ($child->getName() === $fieldname) {
                return $baseLayoutName;
            }
        }

        return null;
    }
}
