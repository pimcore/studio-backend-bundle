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

use Pimcore\Bundle\StudioBackendBundle\Element\Request\PathParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Request\SearchTermParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Subtype;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ElementServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getElementIdByPath(
        string $elementType,
        PathParameter $pathParameter,
        UserInterface $user
    ): int;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getAllowedElementIdByPath(
        string $elementType,
        PathParameter $pathParameter,
        UserInterface $user
    ): int;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getAllowedElementById(
        string $elementType,
        int $elementId,
        UserInterface $user,
    ): ElementInterface;

    /**
     * @throws NotFoundException
     */
    public function getElementById(string $elementType, int $elementId): ElementInterface;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getAllowedElementByPath(
        string $elementType,
        string $elementPath,
        UserInterface $user
    ): ElementInterface;

    public function hasElementChildren(ElementInterface $element): bool;

    public function hasElementDependencies(
        ElementInterface $element
    ): bool;

    /**
     * @throws NotFoundException|ForbiddenException|UserNotFoundException
     */
    public function getElementSubtype(ElementParameters $parameters): Subtype;

    /**
     * @throws InvalidElementTypeException|NotFoundException|SearchException
     */
    public function resolveBySearchTerm(
        string $elementType,
        SearchTermParameter $searchTerm,
        UserInterface $user
    ): int;
}
