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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;

/**
 * @internal
 */
interface PerspectiveConfigRepositoryInterface
{
    /**
     * @throws NotWriteableException
     */
    public function saveConfiguration(string $perspectiveId, array $perspectiveData): void;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function getConfiguration(string $perspectiveId): array;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function listConfigurations(): array;

    /**
     * @throws NotWriteableException
     */
    public function deleteConfiguration(
        string $perspectiveId
    ): void;
}
