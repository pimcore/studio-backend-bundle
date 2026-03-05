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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupDetail;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class GroupDetailEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.classification_store.configuration.group';

    public function __construct(
        private readonly GroupDetail $groupDetail
    ) {
        parent::__construct($groupDetail);
    }

    public function getGroupDetail(): GroupDetail
    {
        return $this->groupDetail;
    }
}
