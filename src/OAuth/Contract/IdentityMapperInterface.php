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
 * Maps external-issuer JWT claims to a Pimcore user.
 *
 * Seam only: external IdP support is not implemented yet. The contract exists
 * so an IdP adapter can be added later without touching the resource-server /
 * MCP endpoints. JIT provisioning, when built, lives behind this seam and is
 * security-sensitive.
 *
 * @internal
 */
interface IdentityMapperInterface
{
    /**
     * @param array<string, mixed> $claims
     */
    public function resolve(array $claims): ?UserInterface;
}
