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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

/**
 * Request-scoped flag telling {@see \Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait}
 * whether the value currently being resolved is a *source field* of an advanced column (picked
 * through its pipeline, with or without a transformer) rather than a plain top-level grid column.
 *
 * The interactive Studio grid intentionally shows a class's default-language value rather than a
 * blank cell once Pimcore's own configured fallback chain still leaves a cell empty (see
 * {@see \Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdapterResolver::allowDefaultLanguageFallback()}).
 * An advanced column's source fields must not get that extra jump - they need to behave
 * identically whether or not the column has a transformer pipeline, which only holds if both
 * branches stick to Pimcore's real, configured fallback chain
 * ({@see \Pimcore\Tool::getFallbackLanguagesFor()}). {@see \Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdvancedColumnResolver}
 * sets this flag for the duration of its own source-field resolution so any sub resolver it calls
 * into can suppress the jump, no matter how many layers of delegation sit in between (e.g. a
 * classification store column delegating to the adapter resolver).
 *
 * @internal
 */
interface AdvancedColumnSourceFieldContextInterface
{
    public function isResolvingSourceField(): bool;

    public function setResolvingSourceField(bool $resolvingSourceField): void;
}
