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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
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
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing as ObjectBrickListing;
use Pimcore\Model\User;
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
        private readonly ObjectBrickServiceInterface $objectBrickService,
        private readonly DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private readonly DataObjectResolverInterface $dataObjectResolver,
        private readonly LoggerInterface $logger
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
        // Like the object editor, enrich the brick layout so options-provider based fields
        // (e.g. Select Options) carry their options into the column configuration.
        $layoutDefinitions = $objectBrick->getLayoutDefinitions();

        /**
         *  @var User $user
         *  Because Core needs a User
         * */
        $user = $this->getUser();
        $this->dataObjectServiceResolver->enrichLayoutDefinition(
            $layoutDefinitions,
            context: [
                'object' => $this->getFolderId() ? $this->dataObjectResolver->getById($this->getFolderId()) : null,
                'purpose' => 'gridconfig',
                'containerType' => 'objectbrick',
                'containerKey' => $objectBrick->getKey(),
                'outerFieldname' => $fieldname,
            ],
            user: $user
        );

        $dataFields = $this->objectBrickService->getDataFields($layoutDefinitions);

        foreach ($dataFields as $dataField) {
            $grouping = [
                $baseLayoutName,
                $fieldname,
                $objectBrick->getKey(),
            ];

            // The brick layout is shared by all outer fields using this brick and is enriched again
            // for each of them, so keep a copy with the options enriched for this outer field.
            $fieldDefinition = clone $dataField->getFieldDefinition();

            try {
                $this->configurations[] = $this->columnConfigurationService->buildDataObjectAdapterColumnConfiguration(
                    new ColumnFieldDefinition($fieldDefinition, $grouping, $dataField->isLocalized()),
                    'dataobject.objectbrick',
                    $fieldname . '.'. $objectBrick->getKey() . '.'. $fieldDefinition->getName(),
                    [
                        'field' => $fieldname,
                        'objectBrick' => $objectBrick->getKey(),
                        'attribute' => $fieldDefinition->getName(),
                    ]
                );
            } catch (InvalidArgumentException $exception) {
                $this->logger->info($exception->getMessage());

                continue;
            }
        }
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
                $found = $this->getBaseLayoutName($fieldname, $child, $child->getTitle() ?: $child->getName());

                if ($found !== null) {
                    return $found;
                }

                continue;
            }

            if ($child->getName() === $fieldname) {
                return $baseLayoutName;
            }
        }

        return null;
    }
}
