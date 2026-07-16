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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Dto;

use Pimcore\Model\UserInterface;

/**
 * Outcome of a successful resource-server token validation: the resolved
 * Pimcore user plus the OAuth context (granted scopes, validated audience,
 * client id).
 *
 * @internal
 */
final readonly class ResolvedAccess
{
    /**
     * @param list<string> $scopes
     * @param list<string> $audience
     */
    public function __construct(
        public UserInterface $user,
        public array $scopes,
        public array $audience,
        public string $clientId,
    ) {
    }
}
