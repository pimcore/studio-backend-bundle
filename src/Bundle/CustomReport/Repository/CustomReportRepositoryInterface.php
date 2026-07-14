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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\User;

/**
 * @internal
 */
interface CustomReportRepositoryInterface
{
    public function loadAll(): array;

    public function loadForUser(User $user): array;

    public function loadForCurrentUser(): array;

    /**
     * @throws NotFoundException
     */
    public function loadByName(string $name): Config;

    /**
     * @throws NotFoundException
     */
    public function loadByNameForCurrentUser(string $name): ?Config;

    /**
     * @throws NotFoundException
     */
    public function loadByNameForUser(string $name, User $user): ?Config;

    /**
     * @throws NotWriteableException
     */
    public function create(string $name): Config;

    /**
     * @throws NotWriteableException
     */
    public function update(Config $config): Config;

    /**
     * @throws NotWriteableException
     */
    public function cloneConfig(Config $existingConfig, string $newName): Config;

    /**
     * @throws NotWriteableException
     */
    public function delete(Config $config): void;

    public function exists(string $name): bool;
}
