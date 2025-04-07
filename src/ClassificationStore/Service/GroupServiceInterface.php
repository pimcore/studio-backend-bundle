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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface GroupServiceInterface
{
    /**
     * @throws Exception
     * @throws NotFoundException
     */
    public function getGroups(ListClassificationStoreParameter $parameter): Collection;

    /**
     * @return array<int, int>
     *
     * @throws Exception
     * @throws NotFoundException
     */
    public function getAllowedGroupIds(ListClassificationStoreParameter $parameter): array;
}
