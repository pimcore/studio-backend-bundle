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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\CustomLayout;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class CustomLayoutCollectionEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.custom_layout.collection';

    public function __construct(private readonly CustomLayoutCompact $customLayoutCollection)
    {
        parent::__construct($this->customLayoutCollection);
    }

    public function getCustomLayoutCompact(): CustomLayoutCompact
    {
        return $this->customLayoutCollection;
    }
}
