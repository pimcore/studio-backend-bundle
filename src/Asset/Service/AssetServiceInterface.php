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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\Asset as AssetModel;
use Pimcore\Model\UserInterface;

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
     * @throws SearchException|NotFoundException|UserNotFoundException
     */
    public function getAsset(
        int $id,
        bool $getWorkflowAvailable = true
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video;

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
     * @throws ForbiddenException|NotFoundException
     */
    public function getAssetElement(
        UserInterface $user,
        int $assetId,
    ): AssetModel;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getAssetElementByPath(
        UserInterface $user,
        string $path,
    ): AssetModel;

    public function getUniqueAssetName(string $targetPath, string $filename): string;

    /**
     * @throws DatabaseException|ForbiddenException
     */
    public function clearThumbnails(int $id): void;
}
