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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\ConfigLayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface LayoutDefinitionServiceInterface
{
    /**
     * @throws InvalidElementTypeException|NotFoundException|Exception
     *
     * @return LayoutDefinition[]
     */
    public function getLayoutDefinitionsForObject(int $dataObjectId): array;

    /**
     * @throws NotFoundException|EnvironmentException
     */
    public function getLayoutDefinitionByKey(string $key): ConfigLayoutDefinition;
}
