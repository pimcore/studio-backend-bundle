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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

/**
 * Marks a response element as carrying the pre-computed flag whether a workflow with
 * place permissions applies to it.
 *
 * @internal
 */
interface WorkflowPermissionsAwareInterface
{
    public function getHasWorkflowWithPermissions(): bool;
}
