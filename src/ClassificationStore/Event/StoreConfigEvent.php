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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\StoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class StoreConfigEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.classification_store.config_collection';

    public function __construct(
        private readonly StoreConfig $storeConfig
    ) {
        parent::__construct($storeConfig);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getStoreConfig(): StoreConfig
    {
        return $this->storeConfig;
    }
}
