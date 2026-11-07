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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Closure;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * @internal
 */
interface ReconcilerInterface
{
    /**
     * Writes the artifact into the store, creating and updating but never deleting: what the store
     * carries and the file does not is left in place and named in the report.
     *
     * The seven sections are applied as one transaction, in the order the store, the sub groups,
     * the collections, the groups, the keys, the collection relations and the key assignments -
     * every one of them needs the ones before it resolved. A conflict does not stop the run: the
     * report names every one that was found and the transaction is rolled back, so a conflicting
     * file leaves nothing behind.
     *
     * The transaction is this apply's own only when there was none to join: an apply inside a
     * transaction a caller opened neither commits nor rolls back, and reports its conflicts to a
     * caller that decides. Where it does roll back, it also takes back the search index remap notes
     * its own key writes left - and only those, because the notes of a write that happened earlier
     * in the same request are about rows this rollback did not touch
     * ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface::discardSince()}).
     *
     * A null store is the console command applying a configuration to an installation that does not
     * carry it yet; the endpoint always has the store of its route.
     *
     * `$onDecision` is handed each decision as one line as it is made, so that a command can print
     * the entries instead of holding them.
     *
     * @param Closure(string): void|null $onDecision
     *
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function apply(
        ArtifactDocument $document,
        ?StoreConfig $store,
        bool $dryRun,
        ?Closure $onDecision = null,
    ): ApplyReport;
}
