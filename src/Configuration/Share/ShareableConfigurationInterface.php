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

namespace Pimcore\Bundle\StudioBackendBundle\Configuration\Share;

use Doctrine\Common\Collections\Collection;

/**
 * @internal
 */
interface ShareableConfigurationInterface
{
    public function getOwner(): ?int;

    public function isShareGlobal(): bool;

    public function setShareGlobal(bool $shareGlobal): void;

    /**
     * @return Collection<int, ConfigurationShareInterface>
     */
    public function getShares(): Collection;

    public function addShare(ConfigurationShareInterface $share): void;

    public function createShare(int $userId): ConfigurationShareInterface;
}
