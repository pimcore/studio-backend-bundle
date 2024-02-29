<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Dto\Token;

use Carbon\Carbon;

/**
 * @internal
 */
final readonly class Output
{
    public function __construct(
        private string $token,
        private int    $lifetime,
        private string $username
    )
    {
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

    public function validUntil(): string
    {
        return Carbon::now()->addSeconds($this->lifetime)->toDateTimeString();
    }
}