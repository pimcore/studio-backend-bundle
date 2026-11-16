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

/**
 * The one place the question core's key save asks of a key's field type is put from this bundle:
 * can the engine of the store hold it. The same three conditions, in the same order, as
 * {@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::save()}, so that a write which
 * would be refused there is named before it is attempted.
 *
 * @internal
 */
interface KeyTypeEngineFitResolverInterface
{
    /**
     * The name of the engine that cannot hold the type, or null where the type is held.
     *
     * Only a type of the accepted set is governed, one held in the two value columns fits every
     * engine, and an engine reporting the `StructuredValues` capability holds the rest. A null
     * `$storeId` is a store that does not exist yet, which will carry the default engine. An
     * installation that offers no engine resolver cannot be asked, and core's own gate cannot
     * refuse there either, so nothing is refused; the same holds for a store whose engine cannot be
     * resolved, which is not thrown from here.
     */
    public function findRefusingEngine(string $type, ?string $delegateType, ?int $storeId): ?string;
}
