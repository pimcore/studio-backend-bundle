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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Model\Element\Note\Listing as NoteListing;

/**
 * @internal
 */
interface FilterServiceInterface
{
    public function applyFilter(NoteListing $list, NoteParameters $parameters): void;

    /**
     * @throws InvalidFilterException
     */
    public function applyFieldFilters(NoteListing $list, NoteParameters $parameters): void;

    public function applyElementFilter(NoteListing $list, NoteElementParameters $noteElement): void;
}
