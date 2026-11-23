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
 * @internal
 */
interface SearchIndexRemapTargetResolverInterface
{
    /**
     * The data object classes whose search index a store reaches, which is one index each and the
     * unit a remap is queued for. A store no class carries a field of resolves to nothing.
     *
     * @return list<string> the data object class ids, each at most once
     */
    public function resolveForStore(int $storeId): array;
}
