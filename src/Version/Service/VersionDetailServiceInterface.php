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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Dimensions;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\ImageVersion;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\UserInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;

/**
 * @internal
 */
interface VersionDetailServiceInterface
{
    /**
     * @throws AccessDeniedException|NotFoundException|InvalidElementTypeException
     */
    public function getVersionData(
        int $id,
        UserInterface $user
    ): AssetVersion|ImageVersion|DataObjectVersion|DocumentVersion;

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    public function getImageDimensions(Image $image): Dimensions;

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    public function getAssetFileSize(Asset $image): ?int;
}
