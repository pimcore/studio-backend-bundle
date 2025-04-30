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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\CustomLayout;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout as CustomLayoutSchema;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

/**
 * @internal
 */
interface CustomLayoutHydratorInterface
{
    public function hydrateCompactLayout(CustomLayout $data): CustomLayoutCompact;

    /**
     * @throws NotFoundException
     */
    public function hydrateLayout(CustomLayout $data): CustomLayoutSchema;
}
