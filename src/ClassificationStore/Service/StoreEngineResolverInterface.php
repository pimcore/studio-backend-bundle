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

/**
 * The one place the capabilities the engine of a classification store reports are read, so that
 * every caller judging a write against them judges it the same way.
 *
 * The answer is published raw and it is tri-state: exactly those capability names the engine answers
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
     * either.
     *
     * @return string[]|null
     *
     * @throws EnvironmentException
     */
    public function getCapabilitiesOfStore(int $storeId): ?array;
}
