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

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\FieldsByTypeParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldByType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;

/**
 * @internal
 */
interface FieldsByTypeServiceInterface
{
    /**
     * @throws UserNotFoundException
     *
     * @return FieldByType[]
     */
    public function getFieldsByType(FieldsByTypeParameters $parameters): array;
}
