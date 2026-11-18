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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Model;

use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;

/**
 * @internal
 */
final readonly class RestoreResult
{
    /**
     * @param RelocatedItem[] $relocatedItems
     */
    public function __construct(
        private ?int $jobRunId = null,
        private array $relocatedItems = [],
    ) {
    }

    /**
     * Set when the items are restored by a job run.
     */
    public function getJobRunId(): ?int
    {
        return $this->jobRunId;
    }

    /**
     * @return RelocatedItem[]
     */
    public function getRelocatedItems(): array
    {
        return $this->relocatedItems;
    }
}
