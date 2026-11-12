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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Contract;

use Pimcore\Model\UserInterface;

/**
 * Resolves a token subject to an effective, enabled Pimcore user.
 *
 * The embedded authorization server uses the numeric Pimcore user id as the
 * token subject. Never widens permissions: it only produces the user whose
 * ACLs are then enforced downstream. Disabled/deleted/locked users resolve to
 * null.
 *
 * @internal
 */
interface IdentityResolverInterface
{
    public function resolve(string $subject): ?UserInterface;
}
