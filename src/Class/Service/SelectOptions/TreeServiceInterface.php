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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTree;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTreeFolder;

/**
 * @internal
 */
interface TreeServiceInterface
{
    /**
     * @return SelectOptionTree[]|SelectOptionTreeFolder[]
     */
    public function getTree(bool $grouped = false): array;
}
