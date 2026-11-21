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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleFieldsConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;

/**
 * @internal
 */
interface RelationVisibleFieldDefinitionFactoryInterface
{
    /**
     * Builds an unsaved relation field definition carrying the given allowed-types settings.
     *
     * @throws InvalidArgumentException
     */
    public function create(RelationVisibleFieldsConfiguration $configuration): ManyToManyRelation;
}
