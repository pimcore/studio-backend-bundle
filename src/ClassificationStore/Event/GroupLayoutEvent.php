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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class GroupLayoutEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.classification_store.group_layout';

    public function __construct(
        private readonly GroupLayout $groupLayout
    ) {
        parent::__construct($groupLayout);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getGroupLayout(): GroupLayout
    {
        return $this->groupLayout;
    }
}
