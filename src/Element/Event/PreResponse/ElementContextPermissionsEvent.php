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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ElementContextPermissionsEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.element.context_permissions';

    public function __construct(
        private readonly AssetContextPermissions|DataObjectContextPermissions|DocumentContextPermissions $permissions
    ) {
        parent::__construct($this->permissions);
    }

    public function getElementContextPermissions(

    ): AssetContextPermissions|DataObjectContextPermissions|DocumentContextPermissions {

        return $this->permissions;
    }
}
