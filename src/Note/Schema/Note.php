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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Schema;

use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
final class Note implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        private readonly int $id,
        private readonly string $type,
        private readonly int $cId,
        private readonly string $cType,
        private readonly string $cPath,
        private readonly int $date,
        private readonly string $title,
        private readonly string $description,
        private readonly bool $locked,
        private readonly array $data,
        private readonly ?int $userId,
        private readonly ?string $userName
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCId(): int
    {
        return $this->cId;
    }

    public function getCType(): string
    {
        return $this->cType;
    }

    public function getCPath(): string
    {
        return $this->cPath;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }
}