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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleField;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleFieldsConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
interface RelationVisibleFieldServiceInterface
{
    /**
     * The fields a many-to-many relation or advanced many-to-many relation with the given (unsaved)
     * allowed-types settings can show as visible fields, in the order the relation offers them.
     *
     * @return RelationVisibleField[]
     *
     * @throws InvalidArgumentException
     */
    public function getAvailableVisibleFields(RelationVisibleFieldsConfiguration $configuration): array;
}
