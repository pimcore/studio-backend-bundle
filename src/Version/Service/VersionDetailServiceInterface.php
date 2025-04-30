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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Dimensions;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface VersionDetailServiceInterface
{
    /**
     * @throws ForbiddenException|NotFoundException|InvalidElementTypeException
     */
    public function getVersionData(
        int $id,
        UserInterface $user
    ): AssetVersion|DataObjectVersion|DocumentVersion;

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    public function getDimensions(Asset $asset): Dimensions;

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    public function getAssetFileSize(Asset $asset): ?int;
}
