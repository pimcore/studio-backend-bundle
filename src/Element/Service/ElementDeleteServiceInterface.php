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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletionFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

interface ElementDeleteServiceInterface
{
    /**
     * @throws ElementDeletionFailedException|EnvironmentException|ForbiddenException|InvalidElementTypeException
     */
    public function deleteParentElement(
        ElementInterface $element,
        UserInterface $user
    ): void;

    /**
     * @throws ElementDeletionFailedException|EnvironmentException
     */
    public function deleteElement(
        ElementInterface $element,
        UserInterface $user
    ): void;

    /**
     * @throws InvalidElementTypeException
     */
    public function useRecycleBinForElement(
        ElementInterface $element,
        UserInterface $user
    ): bool;

    public function addElementToRecycleBin(
        ElementInterface $element,
        UserInterface $user
    ): void;
}
