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

    public function getClassDefinition(): CustomLayoutCompact
    {
        return $this->customLayoutCollection;
    }
}
