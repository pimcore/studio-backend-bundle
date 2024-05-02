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

namespace Pimcore\Bundle\StudioBackendBundle\Version;

use Pimcore\Bundle\StudioBackendBundle\Version\Request\VersionCleanupParameters;
use Pimcore\Bundle\StudioBackendBundle\Version\Request\VersionParameters;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Listing as VersionListing;

/**
 * @internal
 */
interface RepositoryInterface
{
    public function listVersions(
        ElementInterface $element,
        VersionParameters $parameters,
        UserInterface $user
    ): VersionListing;

    public function getLastVersion(
        int $elementId,
        string $elementType,
        UserInterface $user
    ): Version;

    public function getElementFromVersion(
        Version $version,
        UserInterface $user
    ): ElementInterface;

    public function getVersionById(
        int $id
    ): Version;

    public function cleanupVersions(
        VersionCleanupParameters $parameters,
    ): array;
}