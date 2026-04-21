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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\ReportEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\TreeConfigNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\TreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Hydrator\CustomReportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository\CustomReportRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportAdd;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportClone;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeConfigNode;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateConfigurationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class CustomReportConfigService implements CustomReportConfigServiceInterface
{
    use ValidateConfigurationTrait;

    public function __construct(
        private CustomReportHydratorInterface $customReportHydrator,
        private CustomReportRepositoryInterface $customReportRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getCustomReportTree(): array
    {
        $treeData = [];
        $reportTree = $this->customReportRepository->loadForCurrentUser();

        foreach ($reportTree as $report) {
            $data = $this->customReportHydrator->extractTreeData($report);

            $this->eventDispatcher->dispatch(
                new TreeNodeEvent($data),
                TreeNodeEvent::EVENT_NAME
            );

            $treeData[] = $data;
        }

        return $treeData;
    }

    public function getCustomReportConfigTree(bool $grouped = false): array
    {
        $reportTree = $this->customReportRepository->loadAll();

        if (empty($reportTree)) {
            return [];
        }

        if ($grouped) {
            return $this->getGroupedConfigTree($reportTree);
        }

        return $this->getFlatConfigTree($reportTree);
    }

    /**
     * {@inheritdoc}
     */
    public function createCustomReport(CustomReportAdd $parameters): CustomReportDetails
    {
        $configName = $this->getValidConfigName(['name' => $parameters->getName()]);

        if ($this->customReportRepository->exists($configName)) {
            throw new InvalidArgumentException(
                sprintf('Custom report with name "%s" already exists.', $configName)
            );
        }
        $config = $this->customReportRepository->create($configName);

        return $this->customReportHydrator->extractReportDetails($config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateCustomReport(string $name, CustomReportUpdate $parameters): CustomReportDetails
    {
        $customReport = $this->getAllowedReport($name);
        $this->customReportHydrator->dehydrateReportDetails($customReport, $parameters);
        $config = $this->customReportRepository->update($customReport);

        return $this->customReportHydrator->extractReportDetails($config);
    }

    /**
     * {@inheritdoc}
     */
    public function cloneCustomReport(string $reportName, CustomReportClone $parameters): CustomReportDetails
    {
        $newName = $this->getValidConfigName(['name' => $parameters->getNewName()]);
        if ($this->customReportRepository->exists($newName)) {
            throw new InvalidArgumentException(
                sprintf('Custom report with name "%s" already exists.', $newName)
            );
        }

        $reportToClone = $this->getCustomReportByName($reportName);
        $config = $this->customReportRepository->cloneConfig($reportToClone, $newName);

        return $this->customReportHydrator->extractReportDetails($config);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCustomReport(string $name): void
    {
        $customReport = $this->getCustomReportByName($name);
        $this->customReportRepository->delete($customReport);
    }

    public function getCustomReportByName(string $reportName): Config
    {
        return $this->customReportRepository->loadByName($reportName);
    }

    public function getCustomReportDetails(string $reportName): CustomReportDetails
    {
        $config = $this->getAllowedReport($reportName);
        $reportDetails = $this->customReportHydrator->extractReportDetails($config);

        $this->eventDispatcher->dispatch(
            new ReportEvent($reportDetails),
            ReportEvent::EVENT_NAME
        );

        return $reportDetails;
    }

    public function getFieldsForExport(Config $reportConfig): array
    {
        $columns = $reportConfig->getColumnConfiguration();
        $fields = [];
        foreach ($columns as $column) {
            if ($column['export']) {
                $fields[] = $column['name'];
            }
        }

        return $fields;
    }

    public function generateCsvData(array $reportData, array $exportFields, bool $includeHeaders): array
    {
        $csvData = [];

        if (empty($reportData['data'])) {
            return $csvData;
        }

        $data = $reportData['data'];
        if ($includeHeaders) {
            $csvData[] = $exportFields;
        }

        $sortedData = array_map(static function ($row) use ($exportFields) {
            return array_merge(array_flip($exportFields), $row);
        }, $data);

        foreach ($sortedData as $row) {
            $csvData[] = array_values($row);
        }

        return $csvData;
    }

    /**
     * @param Config[] $reports
     *
     * @return CustomReportTreeConfigNode[]
     */
    private function getFlatConfigTree(array $reports): array
    {
        $treeData = [];

        foreach ($reports as $report) {
            $treeData[] = $this->hydrateConfigTreeNode($report);
        }

        return $treeData;
    }

    /**
     * @param Config[] $reports
     *
     * @return array<CustomReportTreeConfigNode|CustomReportTreeNodeFolder>
     */
    private function getGroupedConfigTree(array $reports): array
    {
        $groups = [];
        $ungrouped = [];

        foreach ($reports as $report) {
            $group = $report->getGroup();

            if ($group !== '') {
                if (!isset($groups[$group])) {
                    $groups[$group] = [
                        'groupIconClass' => $report->getGroupIconClass(),
                        'reports' => [],
                    ];
                }

                $groups[$group]['reports'][] = $report;
            } else {
                $ungrouped[] = $report;
            }
        }

        $sortable = [];

        foreach ($ungrouped as $report) {
            $sortable[] = [
                'sortKey' => $report->getName(),
                'type' => 'node',
                'data' => $report,
            ];
        }

        foreach ($groups as $groupName => $groupData) {
            $sortable[] = [
                'sortKey' => $groupName,
                'type' => 'folder',
                'data' => $groupData,
            ];
        }

        usort(
            $sortable,
            static fn (array $a, array $b): int => strnatcasecmp($a['sortKey'], $b['sortKey'])
        );

        $result = [];

        foreach ($sortable as $entry) {
            if ($entry['type'] === 'node') {
                $result[] = $this->hydrateConfigTreeNode($entry['data']);

                continue;
            }

            $result[] = $this->hydrateConfigTreeFolderNode(
                $entry['sortKey'],
                $entry['data']['groupIconClass'],
                $entry['data']['reports']
            );
        }

        return $result;
    }

    private function hydrateConfigTreeNode(Config $report): CustomReportTreeConfigNode
    {
        $node = $this->customReportHydrator->extractConfigTreeData($report);
        $this->eventDispatcher->dispatch(
            new TreeConfigNodeEvent($node),
            TreeConfigNodeEvent::EVENT_NAME
        );

        return $node;
    }

    /**
     * @param Config[] $reports
     */
    private function hydrateConfigTreeFolderNode(
        string $group,
        string $groupIconClass,
        array $reports
    ): CustomReportTreeNodeFolder {
        $children = [];

        foreach ($reports as $report) {
            $children[] = $this->hydrateConfigTreeNode($report);
        }

        $folderNode = $this->customReportHydrator->extractTreeFolderData(
            $group,
            $groupIconClass,
            $children
        );

        $this->eventDispatcher->dispatch(
            new TreeConfigNodeEvent($folderNode),
            TreeConfigNodeEvent::EVENT_NAME
        );

        return $folderNode;
    }

    /**
     * @throws ForbiddenException
     */
    private function getAllowedReport(string $reportName): Config
    {
        $report = $this->customReportRepository->loadByNameForCurrentUser($reportName);

        if ($report === null) {
            throw new ForbiddenException('User does not have access to this report');
        }

        return $report;
    }
}
