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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValues;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValuesRequest;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface RelationVisibleFieldValuesServiceInterface
{
    /**
     * The visible field values of the requested rows of a many-to-many relation or advanced many-to-many
     * relation, one entry per related element the user may view, with one column per visible field the
     * user may see (the columns of getAvailableDataObjectColumnConfigurationForRelation()).
     *
     * @return RelationVisibleFieldValues[]
     *
     * @throws InvalidArgumentException|ParseException
     */
    public function getVisibleFieldValues(RelationVisibleFieldValuesRequest $request, UserInterface $user): array;
}
