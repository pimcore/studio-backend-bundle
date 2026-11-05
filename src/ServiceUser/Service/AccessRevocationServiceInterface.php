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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service;

use Pimcore\Model\User\ServiceUser;

/**
 * Takes away every credential and every MCP server grant a service user holds.
 *
 * @internal
 */
interface AccessRevocationServiceInterface
{
    /**
     * Deletes the bound OAuth client, revokes the user's OAuth tokens by `jti` and its MCP
     * access tokens, and removes the user's entries from every writeable MCP server's access
     * list. Access is matched by name, so a later user with the same name inherits nothing.
     */
    public function revokeAll(ServiceUser $serviceUser): void;
}
