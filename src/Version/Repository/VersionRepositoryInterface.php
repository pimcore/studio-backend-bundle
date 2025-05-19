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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Version\MappedParameter\UpdateVersionParameter;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Listing as VersionListing;

/**
 * @internal
 */
interface VersionRepositoryInterface
{
    /**
     * @throws ForbiddenException
     */
    public function listVersions(
        ElementInterface $element,
        string $originalType,
        CollectionParameters $parameters,
        UserInterface $user
    ): VersionListing;

    public function getLastVersion(
        int $elementId,
        string $elementType,
        UserInterface $user
    ): ?Version;

    /**
     * @throws ForbiddenException
     */
    public function getElementFromVersion(
        Version $version,
        UserInterface $user
    ): ElementInterface;

    /**
     * @throws NotFoundException
     */
    public function getVersionById(
        int $id
    ): Version;

    /**
     * @throws ElementSavingFailedException
     */
    public function updateVersion(
        Version $version,
        UpdateVersionParameter $parameter
    ): void;

    public function cleanupVersions(
        ElementParameters $elementParameters,
        ?int $modificationDate,
    ): array;
}
