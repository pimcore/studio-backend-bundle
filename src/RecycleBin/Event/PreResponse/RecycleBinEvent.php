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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RecycleBin;

final class RecycleBinEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.recycle_bin.item';

    public function __construct(
        private readonly RecycleBin $recycleBin
    ) {
        parent::__construct($this->recycleBin);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getRecycleBin(): RecycleBin
    {
        return $this->recycleBin;
    }
}