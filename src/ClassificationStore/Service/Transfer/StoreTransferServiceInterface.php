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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\ImportReport;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * @internal
 */
interface StoreTransferServiceInterface
{
    /**
     * The store's artifact as the pieces of one JSON document. Refused while the store, or any
     * collection, group, sub group or key in it, carries no identifier - there would be nothing in
     * the file to match those entries by on the way back in.
     *
     * The store is resolved before the first piece is produced, so that a store no id carries is
     * answered with a not found rather than with a half written file.
     *
     * A ConflictException may also be thrown while the returned iterable is iterated, after the response
     * has started: a relation naming an entity the entity sections did not carry (the configuration
     * changed between two of the reads) stops the stream at that entry, and what was written is not
     * valid JSON.
     *
     * @return iterable<string>
     *
     * @throws ConflictException
     * @throws DatabaseException
     * @throws NotFoundException
     */
    public function exportStore(int $storeId): iterable;

    /**
     * The store a console argument names, by identifier first and by row id only failing that.
     *
     * The order is the contract, not an implementation detail. An identifier is a string matching
     * `/^[A-Za-z0-9][A-Za-z0-9_-]*$/`, so a digits-only identifier is legal - and reading a
     * reference as a row id because it happens to be digits would export whichever store sits at
     * that id instead of the one the operator named. The identifier is the identity of a store
     * across installations (requirements register R18); the row id is local to one, so it answers
     * only for a reference no identifier claims.
     *
     * @throws NotFoundException
     */
    public function resolveStore(string $reference): StoreConfig;

    /**
     * Applies the artifact to the store of the route, which this never creates - the route already
     * resolved one. The file decides which of the two imports it is, and the report says which one
     * happened ({@see ImportReport::isReplaced()}).
     *
     * A file describing this store is restored into it: entries are matched by identifier, created
     * and updated, and nothing is deleted. This is the round trip and the cross installation
     * restore, and it is unchanged.
     *
     * A file describing another store is imported as a duplicate. Every member identifier is
     * regenerated for this store ({@see ArtifactDuplicatorInterface}), the store keeps its own
     * identity and name, and what it held is removed before the file is written in its place
     * ({@see StoreMemberPurgerInterface}) - which is what makes applying the same file twice give
     * the same store rather than a second copy of everything in it.
     *
     * A dry run decides all of that and writes nothing, so the caller can show what a replace would
     * remove before it happens.
     *
     * @throws ConflictException
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function importStore(int $storeId, string $json, bool $dryRun = false): ImportReport;

    /**
     * Applies the artifact to the store the file itself names, creating that store when the
     * installation does not carry it yet. Conflicts are reported rather than thrown, so that a dry
     * run can name them all and the caller decides what a refused run exits with.
     *
     * The target is the file's own store identifier and nothing else, so this is reachable from the
     * console alone ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Command\ApplyStoreCommand}):
     * deploying a configuration onto an instance that does not carry it yet has no store to be
     * given. Nothing here ever duplicates or replaces - an operator who wants a configuration in a
     * store of their choosing imports it into that store.
     *
     * @param Closure(string): void|null $onDecision
     *
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function applyArtifact(string $json, bool $dryRun, ?Closure $onDecision = null): ApplyReport;
}
