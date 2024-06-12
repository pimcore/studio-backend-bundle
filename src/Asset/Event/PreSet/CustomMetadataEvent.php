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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreSet;

use Symfony\Contracts\EventDispatcher\Event;

final class CustomMetadataEvent extends Event
{
    public const EVENT_NAME = 'pre_set.asset_custom_metadata';

    public function __construct(
        private readonly int $id,
        private array $customMetadata
    )
    {
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
