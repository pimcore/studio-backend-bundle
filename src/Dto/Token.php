<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use Carbon\Carbon;

/**
 * @internal
 */
final readonly class Token
{
    public function __construct(
        private string $token,
        private int $lifetime,
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

    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    public function validUntil(): int
    {
        return Carbon::now()->addSeconds($this->lifetime)->getTimestamp();
    }
}
