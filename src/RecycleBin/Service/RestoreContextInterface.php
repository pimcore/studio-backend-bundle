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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * Holds the state of the recycle bin restore that is running, so that core save events can take part in it.
 *
 * @internal
 */
interface RestoreContextInterface
{
    public function start(UserInterface $user): void;

    /**
     * Returns null when no recycle bin restore is running.
     */
    public function getUser(): ?UserInterface;

    /**
     * @throws InvalidElementTypeException
     */
    public function addRelocatedElement(ElementInterface $element): void;

    /**
     * @return ElementInterface[]
     */
    public function getRelocatedElements(): array;

    public function clear(): void;
}
