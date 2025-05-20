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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\ExcludeMainSiteParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Site;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\UpdateSiteParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;

/**
 * @internal
 */
interface SiteServiceInterface
{
    /**
     * @return Site[]
     */
    public function getAvailableSites(ExcludeMainSiteParameter $parameter): array;

    /**
     * @throws ForbiddenException|ElementSavingFailedException|UserNotFoundException|NotFoundException
     */
    public function updateSite(int $documentId, UpdateSiteParameters $parameters): void;

    /**
     * @throws ForbiddenException|UserNotFoundException|NotFoundException
     */
    public function deleteSite(int $documentId): void;
}
