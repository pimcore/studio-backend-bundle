<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
