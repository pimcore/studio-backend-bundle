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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateObjectBrickParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * @internal
 */
interface ObjectBrickRepositoryInterface
{
    /**
     * @return Definition[]
     */
    public function listObjectBricks(): array;

    /**
     * @throws NotFoundException
     */
    public function getObjectBrickByKey(string $key): Definition;

    /**
     * @throws ElementExistsException|ElementSavingFailedException|InvalidArgumentException|NotWriteableException
     */
    public function create(CreateObjectBrickParameters $parameters): Definition;

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    public function update(Definition $definition, UpdateParameters $parameters): Definition;

    /**
     * @throws NotWriteableException
     */
    public function delete(Definition $definition): void;

    public function exportAsJson(Definition $definition): string;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotWriteableException
     */
    public function importFromJson(Definition $definition, string $json): Definition;

    public function getObjectBrickUsages(string $key): array;
}
