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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency;

use Pimcore\Model\Dependency;

/**
 * @internal
 */
interface RepositoryInterface
{
    public function listRequiresDependencies(string $elementType, int $elementId): array;
    public function listRequiresDependenciesTotalCount(string $elementType, int $elementId): int;
    public function listRequiredByDependencies(string $elementType, int $elementId): array;
    public function listRequiredByDependenciesTotalCount(string $elementType, int $elementId): int;
}