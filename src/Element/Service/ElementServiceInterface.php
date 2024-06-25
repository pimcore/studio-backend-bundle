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

use Pimcore\Bundle\StudioBackendBundle\Element\Request\PathParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\UserInterface;

interface ElementServiceInterface
{
    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getElementIdByPath(
        string $elementType,
        PathParameter $pathParameter,
        UserInterface $user
    ): int;

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAllowedElementById(
        string $elementType,
        int $elementId,
        UserInterface $user,
    ): ElementInterface;

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAllowedElementByPath(
        string $elementType,
        string $elementPath,
        UserInterface $user
    ): ElementInterface;

    public function hasElementDependencies(
        ElementInterface $element
    ): bool;
}
