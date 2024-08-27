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
