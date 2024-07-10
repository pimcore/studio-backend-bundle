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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Version\VersionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Version\MappedParameter\UpdateVersionParameter;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Listing as VersionListing;

/**
 * @internal
 */
final readonly class VersionRepository implements VersionRepositoryInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private VersionResolverInterface $versionResolver
    ) {

    }

    /**
     * @throws AccessDeniedException
     */
    public function listVersions(
        ElementInterface $element,
        string $originalType,
        CollectionParameters $parameters,
        UserInterface $user
    ): VersionListing {
        $this->securityService->hasElementPermission(
            $element,
            $user,
            ElementPermissions::VERSIONS_PERMISSION
        );

        $limit = $parameters->getPageSize();
        $page = $parameters->getPage();
        $list = $this->getElementVersionsListing(
            $element->getId(),
            $originalType,
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

    /**
     * @throws AccessDeniedException
     */
    public function getElementFromVersion(
        Version $version,
        UserInterface $user
    ): ElementInterface {
        $element = $version->getData();
        $this->securityService->hasElementPermission(
            $element,
            $user,
            ElementPermissions::VERSIONS_PERMISSION
        );

        return $element;
    }

    /**
     * @throws NotFoundException
     */
    public function getVersionById(
        int $id
    ): Version {
        $version = $this->versionResolver->getById($id);
        if (!$version) {
            throw new NotFoundException('Version', $id);
        }

        return $version;
    }

    /**
     * @throws ElementSavingFailedException
     */
    public function updateVersion(
        Version $version,
        UpdateVersionParameter $parameter
    ): void
    {
        if ($parameter->getNote() !== null) {
            $version->setNote($parameter->getNote());
        }

        if ($parameter->isPublic() !== null) {
            $version->setPublic($parameter->isPublic());
        }

        try {
            $version->save();
        } catch (Exception $exception) {
            throw new ElementSavingFailedException(
                $version->getId(),
                $exception->getMessage()
            );
        }
    }

    public function cleanupVersions(
        ElementParameters $elementParameters,
        ?int $modificationDate,
    ): array {
        $deletedVersions = [];
        $list = $this->getVersionListing(
            'cid = ? AND ctype = ? AND date <> ?',
            [
                $elementParameters->getId(),
                $elementParameters->getType(),
                $modificationDate,
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
