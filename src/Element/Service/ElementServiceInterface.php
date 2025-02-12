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
use Pimcore\Bundle\StudioBackendBundle\Element\Request\SearchTermParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Subtype;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException as ApiNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\UserInterface;

interface ElementServiceInterface
{
    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getElementIdByPath(
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
     * @throws ForbiddenException|NotFoundException
     */
    public function getAllowedElementByPath(
        string $elementType,
        string $elementPath,
        UserInterface $user
    ): ElementInterface;

    public function hasElementDependencies(
        ElementInterface $element
    ): bool;

    /**
     * @throws ApiNotFoundException|ForbiddenException|UserNotFoundException
     */
    public function getElementSubtype(ElementParameters $parameters): Subtype;

    /**
     * @throws InvalidElementTypeException
     */
    public function getElementContextPermissions(
        string $elementType
    ): AssetContextPermissions|DataObjectContextPermissions|DocumentContextPermissions;

    /**
     * @throws InvalidElementTypeException|NotFoundException|SearchException
     */
    public function resolveBySearchTerm(
        string $elementType,
        SearchTermParameter $searchTerm,
        UserInterface $user
    ): int;
}
