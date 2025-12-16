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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition as ClassDefinitionSchema;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * @internal
 */
interface ClassDefinitionServiceInterface
{
    /**
     * @return ClassDefinitionList[]
     */
    public function getClassDefinitionCollection(
        bool $creatableOnly = false
    ): array;

    /**
     * @return ClassDefinition[]
     */
    public function getClassDefinitions(bool $creatableOnly = false): array;

    /**
     * @throws NotFoundException
     */
    public function getClassDefinitionByName(string $dataObjectClass): ClassDefinitionSchema;

    /**
     * @throws NotFoundException
     */
    public function getClassDefinitionIdsInsideFolder(
        int $folderId
    ): array;
}
