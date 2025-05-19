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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Pimcore\Bundle\StudioBackendBundle\Entity\Perspective\UserPerspectiveData;

/**
 * @internal
 */
interface PerspectiveRepositoryInterface
{
    public function update(UserPerspectiveData $userPerspectives): UserPerspectiveData;

    public function getByUser(int $user): ?UserPerspectiveData;

    public function getUserActivePerspective(int $user): ?string;

    /**
     * @return string[]
     */
    public function listUserPerspectives(int $user): array;
}
