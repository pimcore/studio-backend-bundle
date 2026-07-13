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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepositoryInterface;
use Psr\Log\LoggerInterface;
use function array_slice;
use function count;
use function is_string;

/**
 * Restricts an element grid query to the elements in a given workflow place. Reuses the
 * workflow-state repository (folders already excluded) to resolve the matching element ids and
 * applies the standard `searchByIds` modifier, so the native listing filters server-side without
 * enumerating ids on the client.
 *
 * @internal
 */
final readonly class WorkflowFilter implements FilterInterface
{
    public const string FILTER_TYPE = 'workflow';

    /**
     * Upper bound on ids handed to the search index, guarding against the OpenSearch terms limit
     * for pathologically large states. Realistic states stay far below this; if a state ever
     * exceeds it the excess is dropped and a warning is logged (not surfaced to the user).
     */
    private const int MAX_IDS = 10000;

    public function __construct(
        private WorkflowElementsRepositoryInterface $elementsRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        $elementType = $this->resolveElementType($query);
        if ($elementType === null) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(self::FILTER_TYPE) as $column) {
            $query = $this->applyWorkflowFilter($column, $query, $elementType);
        }

        return $query;
    }

    private function applyWorkflowFilter(
        ColumnFilter $column,
        QueryInterface $query,
        string $elementType
    ): QueryInterface {
        $workflowName = $column->getKeyWithOutLocale();
        $place = $column->getFilterValue();

        if ($workflowName === '') {
            throw new InvalidArgumentException('Workflow filter requires a workflow name (key).');
        }

        // A specific place narrows to that state; a missing place means "any place in the
        // workflow" (the widget's show-all action), resolved by passing a null state name.
        $stateName = is_string($place) && $place !== '' ? $place : null;

        $rows = $this->elementsRepository->fetchByWorkflowState($workflowName, $stateName, $elementType);

        $ids = [];
        foreach ($rows as $row) {
            $cid = (int) $row['cid'];
            // Skip orphaned/invalid workflow-state rows (e.g. cid 0): the search index's
            // IdsFilter requires strictly positive ids and throws otherwise.
            if ($cid > 0) {
                $ids[] = $cid;
            }
        }

        if (count($ids) > self::MAX_IDS) {
            $this->logger->warning(
                'Workflow drill-down id set exceeds the search cap and was truncated.',
                ['workflow' => $workflowName, 'place' => $place, 'total' => count($ids), 'cap' => self::MAX_IDS]
            );
            $ids = array_slice($ids, 0, self::MAX_IDS);
        }

        // Empty id set intentionally matches nothing (rather than the whole index).
        return $query->searchByIds($ids);
    }

    private function resolveElementType(QueryInterface $query): ?string
    {
        return match (true) {
            $query instanceof DataObjectQueryInterface => ElementTypes::TYPE_DATA_OBJECT,
            $query instanceof AssetQueryInterface => ElementTypes::TYPE_ASSET,
            $query instanceof DocumentQueryInterface => ElementTypes::TYPE_DOCUMENT,
            default => null,
        };
    }
}
