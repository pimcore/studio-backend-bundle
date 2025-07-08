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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ColumnConfigurationForRelationServiceInterface
{
    /**
     * Get available data object column configuration for a Many to Many Relation.
     *
     * @return ColumnConfiguration[]
     */
    public function getAvailableDataObjectColumnConfigurationForRelation(
        string $classId,
        string $relationField,
        UserInterface $user
    ): array;
}
