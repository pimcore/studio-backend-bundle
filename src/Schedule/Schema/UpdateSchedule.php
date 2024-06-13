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

/**
 * @internal
 */
#[Schema(
    title: 'UpdateSchedule',
    required: ['id', 'date', 'active'],
    type: 'object'
)]
final readonly class UpdateSchedule
{
    public function __construct(
        #[Property(description: 'id', type: 'integer', example: 666)]
        private int $id,
        #[Property(description: 'Date of schedule', type: 'integer', example: 1634025600)]
        private int $date,
        #[Property(description: 'Action', type: 'string', enum: ['publish-version', 'delete'])]
        private ?string $action,
        #[Property(description: 'Version ID', type: 'integer', example: 987)]
        private ?int $version,
        #[Property(description: 'Active', type: 'boolean', example: true)]
        private bool $active,
    ) {

    }

    public function getId(): int
    {
        return $this->id;
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
}
