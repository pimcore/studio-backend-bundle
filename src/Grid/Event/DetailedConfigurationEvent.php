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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;

final class DetailedConfigurationEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.grid_detailed_configuration';

    public function __construct(
        private readonly DetailedConfiguration $configuration
    ) {
        parent::__construct($configuration);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getConfiguration(): DetailedConfiguration
    {
        return $this->configuration;
    }
}
