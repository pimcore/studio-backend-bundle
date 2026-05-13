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

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateClassDefinitionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition as ClassDefinitionSchema;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionBrickField;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\JsonExport;

/**
 * @internal
 */
interface ClassDefinitionServiceInterface
{
    /**
     * @throws ElementExistsException|ElementSavingFailedException|UserNotFoundException|NotWriteableException
     */
    public function createClassDefinition(CreateClassDefinitionParameters $parameters): ClassDefinitionSchema;

    /**
     * @throws ConflictException|InvalidArgumentException|ElementSavingFailedException
     * @throws NotFoundException|NotWriteableException|UserNotFoundException
     */
    public function updateClassDefinition(string $id, UpdateParameters $updateParameters): ClassDefinitionSchema;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function deleteClassDefinition(string $id): void;

    /**
     * @throws NotFoundException
     */
    public function exportClassDefinition(string $id): JsonExport;

    /**
     * @throws InvalidArgumentException|ElementSavingFailedException|NotFoundException|NotWriteableException
     */
    public function importClassDefinitionFromJson(string $id, string $json): ClassDefinitionSchema;

    /**
     * @return ClassDefinitionList[]
     */
    public function getClassDefinitionCollection(
        bool $creatableOnly = false
    ): array;

    /**
     * @return ClassDefinitionList[]
     */
    public function getClassDefinitionsWithObjectBricks(): array;

    /**
     * @throws NotFoundException
     */
    public function getClassDefinitionByName(string $dataObjectClass): ClassDefinitionSchema;

    /**
     * @throws NotFoundException
     */
    public function getClassDefinitionById(string $id): ClassDefinitionSchema;

    /**
     * @throws NotFoundException
     */
    public function getClassDefinitionBricks(string $id): array;

    /**
     * @return ClassDefinitionBrickField[]
     *
     * @throws NotFoundException
     */
    public function getClassDefinitionBrickFields(string $id): array;
}
