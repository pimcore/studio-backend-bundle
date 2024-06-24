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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

#[Schema(
    title: 'Schedule',
    required: ['id', 'ctype', 'date', 'active', 'userId', 'username'],
    type: 'object'
)]
final class Schedule implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'integer', example: 666)]
        private readonly int $id,
        #[Property(description: 'ctype', type: 'string', example: 'Type of element')]
        private readonly string $ctype,
        #[Property(description: 'Date of schedule', type: 'integer', example: 1634025600)]
        private readonly int $date,
        #[Property(description: 'Action', type: 'string', enum: ['publish', 'delete'])]
        private readonly ?string $action,
        #[Property(description: 'Version ID', type: 'integer', example: 987)]
        private readonly ?int $version,
        #[Property(description: 'Active', type: 'boolean', example: true)]
        private readonly bool $active,
        #[Property(description: 'User ID', type: 'integer', example: 999)]
        private readonly int $userId,
        #[Property(description: 'Username', type: 'string', example: 'shaquille.oatmeal')]
        private readonly string $username
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
