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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter;

/**
 * @internal
 */
final readonly class WorkflowElementsParameters
{
    private const int MAX_PAGE_SIZE = 200;

    public function __construct(
        private string $workflowName = '',
        private ?string $stateName = null,
        private ?string $elementType = null,
        private int $page = 1,
        private int $pageSize = 50,
    ) {
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getStateName(): ?string
    {
        return $this->stateName;
    }

    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    public function getPage(): int
    {
        return max(1, $this->page);
    }

    public function getPageSize(): int
    {
        return max(1, min(self::MAX_PAGE_SIZE, $this->pageSize));
    }
}
