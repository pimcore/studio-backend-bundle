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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Dimensions;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\ImageVersion;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class VersionDetailService implements VersionDetailServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private readonly VersionRepositoryInterface $repository,
        private readonly ServiceProviderInterface $versionHydratorLocator
    ) {
    }

    /**
     * @throws AccessDeniedException|NotFoundException|InvalidElementTypeException
     */
    public function getVersionData(
        int $id,
        UserInterface $user
    ): AssetVersion|ImageVersion|DataObjectVersion|DocumentVersion {
        $version = $this->repository->getVersionById($id);
        $element = $this->repository->getElementFromVersion($version, $user);

        return $this->hydrate(
            $element,
            $this->getElementClass($element)
        );
    }

    /**
     * We have to get the image dimensions from the local file
     * The Image class returns only dimensions of the latest version
     *
     * @throws ElementStreamResourceNotFoundException
     */
    public function getImageDimensions(Image $image): Dimensions
    {
        $path = $this->getLocalAssetPath($image);

        if (is_readable($path)) {
            $assetDimensions = getimagesize($path);
            if ($assetDimensions && $assetDimensions[0] && $assetDimensions[1]) {
                return new Dimensions(
                    $assetDimensions[0],
                    $assetDimensions[1]
                );
            }
        }

        return new Dimensions();
    }

    /**
     *
     *  We have to get the asset file size from the local file
     *  The Asset class returns only file size of the latest version
     *
     * @throws ElementStreamResourceNotFoundException
     */
    public function getAssetFileSize(Asset $image): ?int
    {
        $path = $this->getLocalAssetPath($image);

        if (is_readable($path)) {
            return filesize($path);
        }

        return null;
    }

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    private function getLocalAssetPath(Asset $asset): string
    {
        try {
            return $asset->getLocalFile();
        } catch (Exception) {
            throw new ElementStreamResourceNotFoundException(
                $asset->getId(),
                $asset->getType()
            );
        }
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function hydrate(
        ElementInterface $element,
        string $class
    ): AssetVersion|ImageVersion|DocumentVersion|DataObjectVersion {
        if ($this->versionHydratorLocator->has($class)) {
            return $this->versionHydratorLocator->get($class)->hydrate($element);
        }

        throw new InvalidElementTypeException($class);
    }
}
