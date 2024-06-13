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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
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
     * @throws AccessDeniedException|NotFoundException
     */
    public function getVersions(
        ElementParameters $elementParameters,
        CollectionParameters $parameters,
        UserInterface $user
    ): Collection;

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws InvalidElementTypeException
     * @throws ElementPublishingFailedException
     */
    public function publishVersion(
        int $versionId,
        UserInterface $user
    ): int;

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function cleanupVersions(
        ElementParameters $elementParameters,
        UserInterface $user
    ): array;
}
