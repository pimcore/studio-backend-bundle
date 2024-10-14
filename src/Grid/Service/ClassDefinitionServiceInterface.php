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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

/**
 * @internal
 */
interface ClassDefinitionServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getFilteredLayoutDefinitions(string $classId, int $folderId): ?Layout;

    /**
     * @throws NotFoundException
     */
    public function getFilteredFieldDefinitions(string $classId, int $folderId): array;

    /**
     * @throws NotFoundException
     */
    public function getClassDefinition(string $classId): ClassDefinition;
}