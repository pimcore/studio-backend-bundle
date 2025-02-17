<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
