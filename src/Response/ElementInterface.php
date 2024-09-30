<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
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