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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpToken;

/**
 * MCP access tokens for service users, the interim credential until OAuth client_credentials
 * is enabled. Static personal access tokens are never issued here.
 *
 * @internal
 */
interface McpTokenServiceInterface
{
    public const int MAX_TTL_DAYS = 90;

    /**
     * Issues a new token; earlier tokens stay valid until they expire or are revoked. The
     * plain token is returned only here.
     *
     * @throws InvalidArgumentException when the lifetime is outside 1 to 90 days or the service user is inactive
     * @throws NotFoundException when the id is not a service user
     */
    public function issueToken(int $serviceUserId, int $ttlDays): ServiceUserMcpToken;

    /**
     * Revokes every MCP access token of the service user.
     *
     * @throws NotFoundException when the id is not a service user
     */
    public function revokeTokens(int $serviceUserId): void;
}
