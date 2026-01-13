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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\AvailableVisibleFieldsParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\AvailableVisibleField;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface ClassServiceInterface
{
    /**
     * @throws NotFoundException
     *
     * @return AvailableVisibleField[]
     */
    public function getAvailableVisibleFields(AvailableVisibleFieldsParameters $parameters): array;
}
