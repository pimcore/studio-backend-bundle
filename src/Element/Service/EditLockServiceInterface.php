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

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\EditLock;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface EditLockServiceInterface
{
    /**
     * @throws InvalidElementTypeException|NotFoundException|ForbiddenException
     */
    public function getEditLock(int $id, string $elementType): EditLock;

    /**
     * @throws InvalidElementTypeException|NotFoundException|ForbiddenException
     */
    public function lockElement(int $id, string $elementType): void;

    /**
     * @throws InvalidElementTypeException|NotFoundException|ForbiddenException
     */
    public function unlockElement(int $id, string $elementType): void;
}
