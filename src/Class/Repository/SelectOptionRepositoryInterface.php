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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\DataObject\SelectOptions\Config;

/**
 * @internal
 */
interface SelectOptionRepositoryInterface
{
    /**
     * @return Config[]
     */
    public function listSelectOptions(): array;

    /**
     * @throws NotFoundException
     */
    public function getById(string $id): Config;

    /**
     * @throws ElementExistsException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function create(string $id): Config;

    /**
     * @throws ElementSavingFailedException
     * @throws NotWriteableException
     */
    public function save(Config $config): void;

    /**
     * @throws ConflictException
     * @throws NotWriteableException
     */
    public function delete(Config $config): void;

    public function isWriteable(Config $config): bool;

    /**
     * @return array<string, string[]>
     */
    public function getFieldsUsedIn(Config $config): array;
}
