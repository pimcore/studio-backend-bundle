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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateSelectOptionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateSelectOptionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionUsageItem;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;

/**
 * @internal
 */
interface SelectOptionServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getSelectOption(string $id): SelectOptionDetail;

    /**
     * @throws ElementExistsException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function createSelectOption(CreateSelectOptionParameters $parameters): SelectOptionDetail;

    /**
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws NotWriteableException
     */
    public function updateSelectOption(string $id, UpdateSelectOptionParameters $parameters): SelectOptionDetail;

    /**
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws NotWriteableException
     */
    public function deleteSelectOption(string $id): void;

    /**
     * @throws NotFoundException
     *
     * @return SelectOptionUsageItem[]
     */
    public function getSelectOptionUsages(string $id): array;
}
