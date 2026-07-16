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

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\DeleteInfo;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\IdsParameter;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface BatchDeleteInfoServiceInterface
{
    /**
     * Aggregates the single-element delete info over the given elements: whether any of them has
     * dependencies and whether the whole selection can still use the recycle bin. The aggregation
     * stops as soon as both results are final.
     *
     * @throws ForbiddenException|InvalidElementTypeException|NotFoundException|SearchException
     */
    public function getBatchDeleteInfo(
        IdsParameter $ids,
        string $elementType,
        UserInterface $user
    ): DeleteInfo;
}
