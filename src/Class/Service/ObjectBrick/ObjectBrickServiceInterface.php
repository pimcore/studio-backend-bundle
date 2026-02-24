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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateObjectBrickParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrickUsageData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
interface ObjectBrickServiceInterface
{
    public function listObjectBricks(): Collection;

    /**
     * @throws NotFoundException
     */
    public function getObjectBrickByKey(string $key): ObjectBrickDetail;

    /**
     * @throws ElementExistsException|ElementSavingFailedException|NotWriteableException
     */
    public function createObjectBrick(CreateObjectBrickParameters $parameters): ObjectBrickDetail;

    /**
     * @throws ElementSavingFailedException|NotFoundException|NotWriteableException
     */
    public function updateObjectBrick(string $key, UpdateParameters $parameters): ObjectBrickDetail;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function deleteObjectBrick(string $key): void;

    /**
     * @throws NotFoundException
     */
    public function exportObjectBrick(string $key): Response;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function importObjectBrickFromJson(string $key, string $json): ObjectBrickDetail;

    /**
     * @throws NotFoundException
     *
     * @return ObjectBrickUsageData[]
     */
    public function getObjectBrickUsages(string $key): array;
}
