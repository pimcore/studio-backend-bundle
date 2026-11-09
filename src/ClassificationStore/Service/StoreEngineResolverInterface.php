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

use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * The one place the engine of a classification store and the capabilities that engine reports are
 * read, so that the store tree, the store detail and the object data path cannot drift apart on
 * what a store is able to do.
 *
 * Both answers are published raw and both are tri-state: the engine the store stores, or null where
 * it stores none; and exactly those capability names the engine answers `supports()` true for, or
 * null where the core this bundle runs against cannot be asked at all. An empty array is an engine
 * that reports no capability, which is not the same answer as null.
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
     * The engine the store stores, or null where it stores none.
     *
     * `composer.json` allows a core whose `classificationstore_stores` has no `engine` column, and
     * such a core has no accessor for it: there, every store reports null for this member and for
     * the capabilities as well.
     */
    public function getEngine(StoreConfig $storeConfig): ?string;

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
}
