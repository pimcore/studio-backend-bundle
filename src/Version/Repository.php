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

use Pimcore\Bundle\StaticResolverBundle\Models\Version\VersionResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementPermissionTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Request\VersionCleanupParameters;
use Pimcore\Bundle\StudioBackendBundle\Version\Request\VersionParameters;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Listing as VersionListing;

/**
 * @internal
 */
final class Repository implements RepositoryInterface
{
    use ElementPermissionTrait;
    use ElementProviderTrait;

    public function __construct(
        private readonly VersionResolver $versionResolver
    ) {

    }

    public function listVersions(
        ElementInterface $element,
        VersionParameters $parameters,
        UserInterface $user
    ): VersionListing {
        $this->isAllowed($element, $user, Permissions::VERSIONS_PERMISSION);
        $limit = $parameters->getPageSize();
        $page = $parameters->getPage();
        $list = $this->getElementVersionsListing(
            $parameters->getElementId(),
            $parameters->getElementType(),
            $user->getId()
        );
        $paginatedList = $list;
        $paginatedList->setLimit($limit);
        $paginatedList->setOffset(($page - 1) * $limit);

        return $paginatedList;
    }

    public function getLastVersion(
        int $elementId,
        string $elementType,
        UserInterface $user
    ): ?Version {
        $list = $this->getElementVersionsListing(
            $elementId,
            $elementType,
            $user->getId()
        );
        $list->setLimit(1);
        $versions = $list->load();

        if (empty($versions)) {
            return null;
        }

        return $versions[0];
    }

    public function getElementFromVersion(
        Version $version,
        UserInterface $user
    ): ElementInterface {
        $element = $version->getData();
        $this->isAllowed($element, $user, Permissions::VERSIONS_PERMISSION);

        return $element;
    }

    public function getVersionById(
        int $id
    ): Version {
        $version = $this->versionResolver->getById($id);
        if (!$version) {
            throw new ElementNotFoundException($id);
        }

        return $version;
    }

    public function cleanupVersions(
        VersionCleanupParameters $parameters
    ): array {
        $deletedVersions = [];
        $list = $this->getVersionListing(
            'cid = ? AND ctype = ? AND date <> ?',
            [
                $parameters->getElementId(),
                $parameters->getElementType(),
                $parameters->getElementModificationDate(),
            ]
        );
        $versions = $list->load();

        foreach ($versions as $version) {
            $deletedVersions[] = $version->getId();
            $version->delete();
        }

        return $deletedVersions;
    }

    private function getElementVersionsListing(
        int $elementId,
        string $elementType,
        int $userId
    ): VersionListing {
        return $this->getVersionListing(
            'cid = ? AND ctype = ? AND (autoSave=0 OR (autoSave=1 AND userId = ?))',
            [
                $elementId,
                $elementType,
                $userId,
            ]
        );
    }

    private function getVersionListing(
        string $condition,
        array $params,
    ): VersionListing {
        $list = new VersionListing();
        $list->setLoadAutoSave(true);
        $list->setCondition($condition, $params)
            ->setOrderKey('id')
            ->setOrder('DESC');

        return $list;
    }
}
