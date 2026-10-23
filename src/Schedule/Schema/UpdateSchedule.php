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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ScheduleActions;

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
        #[Property(description: 'Id of schedule, if null a new one will be created', type: 'integer', example: 666)]
        private ?int $id,
        #[Property(description: 'Date of schedule', type: 'integer', example: 1634025600)]
        private int $date,
        #[Property(
            description: 'Action',
            type: 'string',
            enum: [
                ScheduleActions::PublishVersion->value,
                ScheduleActions::Publish->value,
                ScheduleActions::Unpublish->value,
                ScheduleActions::Delete->value,
            ],
            example: 'publish-version'
        )]
        private ?string $action,
        #[Property(description: 'Version ID', type: 'integer', example: 987)]
        private ?int $version,
        #[Property(description: 'Active', type: 'boolean', example: true)]
        private bool $active,
    ) {

    }

    public function getId(): ?int
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
