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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SavedSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DetailedConfiguration;

/**
 * @internal
 */
interface SavedSearchConfigurationServiceInterface
{
    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function getSavedSearchConfiguration(int $id): DetailedConfiguration;

    /**
     * @throws NotFoundException
     */
    public function saveConfiguration(SavedSearchParameter $parameter): Configuration;
}
