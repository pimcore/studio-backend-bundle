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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

/**
 * @internal
 */
interface JobServiceInterface
{
    public function createJob(string $jobName, string $jobStepName, string $messageFQCN, array $items): int;

    /**
     * @param int[] $sortedItemIds
     */
    public function createRestoreJob(array $sortedItemIds): int;
}
