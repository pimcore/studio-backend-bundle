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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;

interface ElementIndexServiceInterface
{
    /**
     * @throws DatabaseException|EnvironmentException
     */
    public function indexRelatedElements(ElementInterface $element, int $newIndex): void;

    /**
     * @throws DatabaseException|EnvironmentException
     */
    public function reindexBasedOnSortBy(AbstractObject $parentObject, string $currentSortOrder): void;
}
