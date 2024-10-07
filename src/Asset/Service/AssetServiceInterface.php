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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\Asset as AssetModel;
use Pimcore\Model\UserInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;

/**
 * @internal
 */
interface AssetServiceInterface
{
    /**
     * @throws InvalidFilterServiceTypeException|SearchException|InvalidQueryTypeException|InvalidFilterTypeException
     */
    public function getAssets(ElementParameters $parameters): Collection;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAsset(int $id): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetForUser(
        int $id,
        UserInterface $user
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetFolder(int $id, bool $checkPermissionsForCurrentUser = true): AssetFolder;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetFolderForUser(
        int $id,
        UserInterface $user
    ): AssetFolder;

    /**
     * @throws SearchException|NotFoundException
     */
    public function assetFolderExists(int $id, bool $checkPermissionsForCurrentUser = true): bool;

    /**
     * @throws SearchException|NotFoundException
     */
    public function assetFolderExistsForUser(int $id, UserInterface $user): bool;

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAssetElement(
        UserInterface $user,
        int $assetId,
    ): AssetModel;

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAssetElementByPath(
        UserInterface $user,
        string $path,
    ): AssetModel;

    public function getUniqueAssetName(string $targetPath, string $filename): string;
}
