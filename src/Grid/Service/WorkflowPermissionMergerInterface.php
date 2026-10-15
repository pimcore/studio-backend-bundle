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

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
interface WorkflowPermissionMergerInterface
{
    /**
     * Returns a new permissions object with the workspace/user permissions further restricted by
     * the element's current workflow place permissions. A permission that is allowed for the user
     * but denied by the workflow is returned as denied; permissions the workflow does not control
     * are left untouched.
     */
    public function mergeWorkflowPermissions(Permissions $permissions, ElementInterface $element): Permissions;
}
