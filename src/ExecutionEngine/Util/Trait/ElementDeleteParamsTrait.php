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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait;

/**
 * @internal
 */
trait ElementDeleteParamsTrait
{
    private bool $deletionAllowed = true;

    private string $reason;

    public function getDeletionAllowed(): bool
    {
        return $this->deletionAllowed;
    }

    public function setDeletionAllowed(bool $deletionAllowed): void
    {
        $this->deletionAllowed = $deletionAllowed;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}
