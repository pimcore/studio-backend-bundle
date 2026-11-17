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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Repository;

use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Model\CloneDescendants;
use Pimcore\Model\Asset;

/**
 * @internal
 */
interface CloneDescendantsRepositoryInterface
{
    /**
     * Loads all descendants of the given asset from the database, the folders ordered parents first.
     */
    public function getDescendants(Asset $asset): CloneDescendants;
}
