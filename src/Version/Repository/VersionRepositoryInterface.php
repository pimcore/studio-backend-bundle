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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
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
     * @throws AccessDeniedException
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
     * @throws AccessDeniedException
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
