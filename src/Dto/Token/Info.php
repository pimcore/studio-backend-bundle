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

/**
 * @internal
 */
final readonly class Info
{
    public function __construct(
        private string $token,
        private string $tmpStoreId,
        private string $username
    )
    {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTmpStoreId(): string
    {
        return $this->tmpStoreId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}