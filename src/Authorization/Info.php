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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization;

/**
 * @internal
 */
final readonly class Info
{
    public function __construct(
        private string $token,
        private string $username
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
