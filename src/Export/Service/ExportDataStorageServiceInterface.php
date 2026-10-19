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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;

/**
 * Keeps the rows collected for an export outside of the job run.
 *
 * The job run row is loaded and written again by every step and every log entry of the execution engine, so
 * everything stored in its context is moved over and over. Collected rows are therefore written once per batch
 * to the temp storage and only read again when the export file is created.
 *
 * @internal
 */
interface ExportDataStorageServiceInterface
{
    /**
     * Stores the rows of one batch. Storing the same batch again replaces it, so a retried step does not
     * duplicate rows.
     *
     * @throws EnvironmentException
     */
    public function store(int $jobRunId, int $batch, array $rows): void;

    /**
     * Returns the rows of all batches in the order of their batch number or null if nothing was stored.
     *
     * @throws EnvironmentException
     */
    public function load(int $jobRunId): ?array;

    /**
     * Removes all stored batches. Never throws, a leftover must not fail an export that already succeeded or
     * hide the reason why it failed.
     */
    public function cleanUp(int $jobRunId): void;
}
