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

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\DeleteInfo;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletionFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

interface ElementDeleteServiceInterface
{
    /**
     * @throws AccessDeniedException
     * @throws ElementDeletionFailedException
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function deleteElements(
        ElementParameters $elementParameters,
        UserInterface $user
    ): ?int;

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

    public function getElementDeleteInfo(
        ElementInterface $element,
        UserInterface $user
    ): DeleteInfo;
}
