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

use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;

/**
 * @internal
 */
interface SubGroupVocabularyResolverInterface
{
    /**
     * The sub group vocabulary of a store, keyed by identifier.
     *
     * @return array<string, SubGroupConfig>
     */
    public function getByStore(int $storeId): array;

    public function clear(): void;
}
