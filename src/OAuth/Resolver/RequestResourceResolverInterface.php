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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Resolver;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Symfony\Component\HttpFoundation\Request;

/**
 * Which registered protected resource a request is addressed to.
 *
 * The endpoints behind the MCP firewall are owned by other bundles, so the
 * audience cannot be derived from a path this bundle knows. It is resolved from
 * the registry instead: whoever owns an endpoint registers it, and that
 * registration is what names the audience.
 *
 * @internal
 */
interface RequestResourceResolverInterface
{
    /**
     * The most specific registered resource covering this request, or null when
     * none does - meaning no audience-bound token can address this endpoint.
     */
    public function resolve(Request $request): ?ProtectedResource;
}
