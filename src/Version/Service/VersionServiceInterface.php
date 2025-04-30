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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Version\Response\Collection;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface VersionServiceInterface
{
    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getVersions(
        ElementParameters $elementParameters,
        CollectionParameters $parameters,
        UserInterface $user
    ): Collection;

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws InvalidElementTypeException
     * @throws ElementPublishingFailedException
     */
    public function publishVersion(
        int $versionId,
        UserInterface $user
    ): int;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function cleanupVersions(
        ElementParameters $elementParameters,
        UserInterface $user
    ): array;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function deleteVersion(
        int $versionId,
        UserInterface $user
    ): void;
}
