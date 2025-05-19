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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;

interface StudioElementInterface
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

    public function getPermissions(): Permissions;
}
