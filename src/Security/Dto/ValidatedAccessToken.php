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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Dto;

use Pimcore\Model\UserInterface;

/**
 * Outcome of a successful MCP access token validation: the bound user and the
 * `reference` (chat session id) the token was minted for.
 *
 * @internal
 */
final readonly class ValidatedAccessToken
{
    public function __construct(
        public UserInterface $user,
        public string $reference,
    ) {
    }
}
