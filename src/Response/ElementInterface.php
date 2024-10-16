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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

/**
 * @internal
 */
interface ElementInterface
{
    public function getId(): int;

    public function getParentId(): int;

    public function getPath(): string;

    public function getIcon(): ElementIcon;

    public function getUserModification(): ?int;

    public function getCreationDate(): ?int;

    public function getModificationDate(): ?int;

    public function getUserOwner(): int;

    public function getLocked(): ?string;

    public function getIsLocked(): bool;
}
