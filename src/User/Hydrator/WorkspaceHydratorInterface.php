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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Model\User\UserRoleInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface WorkspaceHydratorInterface
{
    /**
     * @return UserWorkspace[]
     */
    public function hydrateAssetWorkspace(UserInterface|UserRoleInterface $user): array;

    /**
     * @return UserWorkspace[]
     */
    public function hydrateDataObjectWorkspace(UserInterface|UserRoleInterface $user): array;

    /**
     * @return UserWorkspace[]
     */
    public function hydrateDocumentWorkspace(UserInterface|UserRoleInterface $user): array;
}
