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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\ConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\ConfigurationParameter as DataObjectConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;

/**
 * @internal
 */
interface SaveConfigurationServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function saveAssetGridConfiguration(ConfigurationParameter $configuration): Configuration;

    public function saveDataObjectGridConfiguration(
        DataObjectConfigurationParameter $configuration,
        string $classId
    ): Configuration;
}
