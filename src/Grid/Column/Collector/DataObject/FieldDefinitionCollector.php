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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

/**
 * @internal
 */
final class FieldDefinitionCollector implements ColumnCollectorInterface, ClassIdInterface, FolderIdInterface
{
    use UseClassIdTrait;
    use UseFolderIdTrait;

    /**
     * @var ColumnFieldDefinition[]
     */
    private array $groupedDefinitions = [];

    public function __construct(
        private readonly ClassDefinitionServiceInterface $classDefinitionService,
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService
    ) {
    }

    public function getCollectorName(): string
    {
        return 'data-object-field-definition';
    }

    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
        $layoutDefinitions = $this->classDefinitionService->getFilteredLayoutDefinitions(
            $this->getClassId(),
            $this->getFolderId()
        );

        $classDefinition = $this->classDefinitionService->getClassDefinition($this->getClassId());

        $children = $layoutDefinitions->getChildren();

        $this->groupDefinitions($children, true, $classDefinition->getName(), false);

        return $this->buildColumnConfigurations();
    }

    private function groupDefinitions(
        array $definitions,
        bool $searchForGroup,
        string $defaultGroup,
        bool $localized
    ): void {
        foreach ($definitions as $definition) {
            if ($definition instanceof Layout && $definition->getTitle() && $searchForGroup) {
                $this->groupDefinitions(
                    $definition->getChildren(),
                    false,
                    $definition->getTitle(),
                    $localized
                );

                continue;
            }

            if ($definition instanceof Layout) {
                $this->groupDefinitions($definition->getChildren(), $searchForGroup, $defaultGroup, $localized);

                continue;
            }

            if ($definition instanceof Localizedfields) {
                $this->groupDefinitions($definition->getChildren(), $searchForGroup, $defaultGroup, true);

                continue;
            }

            if (!$definition instanceof Data || $definition instanceof Objectbricks) {
                continue;
            }

            if ($localized) {
                $this->groupedDefinitions[] = new ColumnFieldDefinition($definition, $defaultGroup, true);

                continue;
            }

            $this->groupedDefinitions[] = new ColumnFieldDefinition($definition, $defaultGroup, false);
        }
    }

    /**
     * @return ColumnConfiguration[]
     */
    private function buildColumnConfigurations(): array
    {
        $columns = [];
        foreach ($this->groupedDefinitions as $definition) {
            $columns[] = $this->columnConfigurationService->buildColumnConfiguration($definition);
        }

        return $columns;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_DATA_OBJECT,
        ];
    }
}
