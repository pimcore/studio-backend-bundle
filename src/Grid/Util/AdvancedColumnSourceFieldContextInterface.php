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

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdapterResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdvancedColumnResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;

/**
 * Set by {@see AdvancedColumnResolver} while it resolves an advanced column's source fields for export.
 *
 * While set, {@see AdapterResolver::allowDefaultLanguageFallback()} turns off the extra jump to the system
 * default language from {@see LocalizedValueTrait}, so only Pimcore's configured fallback languages apply,
 * the same as an export without a transformer.
 *
 * This is a shared service, so the flag lives for the whole process in workers. Whoever sets it must restore
 * the previous value in a `finally` block.
 *
 * @internal
 */
interface AdvancedColumnSourceFieldContextInterface
{
    public function isResolvingSourceField(): bool;

    public function setResolvingSourceField(bool $resolvingSourceField): void;
}
