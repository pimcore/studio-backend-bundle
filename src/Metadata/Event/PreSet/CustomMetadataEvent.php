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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Event\PreSet;

use Symfony\Contracts\EventDispatcher\Event;

final class CustomMetadataEvent extends Event
{
    public const EVENT_NAME = 'pre_set.asset_custom_metadata';

    public function __construct(
        private readonly int $id,
        private array $customMetadata
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomMetadata(): array
    {
        return $this->customMetadata;
    }

    public function setCustomMetadata(array $customMetadata): void
    {
        $this->customMetadata = $customMetadata;
    }
}
