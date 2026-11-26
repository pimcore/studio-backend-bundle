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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * The one place the engine of a classification store and the capabilities that engine reports are
 * read, so that the store tree, the store detail and the object data path cannot drift apart on
 * what a store is able to do, and so that every caller judging a write against those capabilities
 * judges it the same way.
 *
 * What the reporting surfaces publish is published raw and is tri-state: the engine the store
 * stores, or null where it stores none; and exactly those capability names the engine answers
 * `supports()` true for, or null where the core this bundle runs against cannot be asked at all. An
 * empty array is an engine that reports no capability, which is not the same answer as null.
 *
 * @internal
 */
interface StoreEngineResolverInterface
{
    /**
     * The capability a write needs to address more than one position of one variant.
     */
    public const string FEATURE_MULTI_VALUE = 'MultiValue';

    /**
     * The capability a write needs to address a variant other than the default one.
     */
    public const string FEATURE_VARIANTS = 'Variants';

    /**
     * The engine the store's values are RESOLVED to, which for a store whose column is empty is the
     * default engine rather than nothing.
     *
     * Deliberately the resolved engine and not the raw column: an empty column means the default
     * engine, so reporting it raw made a store that is on the EAV engine report no engine at all -
     * and the engine indicator of the configuration screen then rendered nothing where it should
     * say which engine the store is on. Every store created before this release carries that empty
     * column, so the two answers differ for exactly the stores the question is about.
     *
     * Null only where the question cannot be put: `composer.json` allows a core whose
     * `classificationstore_stores` has no `engine` column and which has no accessor for it, and a
     * store whose engine does not resolve is not guessed at. That is the same case the capabilities
     * answer null for, so a client sees "no engine" and "no capabilities" together or neither.
     */
    public function getEngine(StoreConfig $storeConfig): ?string;

    /**
     * The engine name a store that is being CREATED is given, or null on an installation that
     * cannot be asked or does not register one - a caller then writes no engine, and the store
     * means the default engine the way every store did before the column existed.
     *
     * Not the default engine: an empty column is what every upgraded store carries, and that is the
     * one value a new store must not be left with, because the capabilities its keys may declare
     * are decided by it. Core owns which engine that is
     * ({@see \Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver::getPreferredEngine()});
     * this bundle only writes what core names.
     */
    public function getEngineForNewStore(): ?string;

    /**
     * The capability names the store's engine reports, resolved from the configuration already
     * loaded so that reading a whole store tree adds no query.
     *
     * This is the answer the store tree publishes, so a store whose engine does not resolve reports
     * null here: "this engine supports nothing" would be a claim the tree has no evidence for, and
     * a tree read must not fail over one unresolvable store either.
     *
     * @return string[]|null
     */
    public function getEngineCapabilities(StoreConfig $storeConfig): ?array;

    /**
     * The capability names the engine of the store reports, for a caller that holds a store id - the
     * object data path, which is reached from a class definition field.
     *
     * An engine that could not be resolved at all is not answered: a failure to resolve one is not a
     * statement about what that engine holds, and a write would otherwise proceed, or be refused, on
     * a verdict nobody reached.
     *
     * Null is a core that cannot be asked at all, and the write path refuses on it exactly as it
     * does on the empty set an engine reporting no capability answers with
     * ({@see QualifiedValueSerializerInterface::assertSupported()}), so neither answer lets a write
     * needing an unconfirmed capability through. A write that needs no capability succeeds against
     * either. This is the write gate and is deliberately not the tri-state the reporting surfaces
     * above publish: there null is "no answer", here it is a capability not confirmed.
     *
     * @return string[]|null
     *
     * @throws EnvironmentException
     */
    public function getCapabilitiesOfStore(int $storeId): ?array;
}
