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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;

/**
 * @internal
 */
interface NotificationTypeRegistryInterface
{
    /**
     * The `notifications`.`type` column is VARCHAR(20); enforced on collection so an over-long
     * id fails loudly instead of being silently truncated into a type that matches nothing.
     */
    public const int MAX_TYPE_ID_LENGTH = 20;

    /**
     * All registered types, ordered by sort order then type id.
     *
     * @return NotificationType[]
     */
    public function getTypes(): array;

    /**
     * @throws NotFoundException
     */
    public function getType(string $typeId): NotificationType;

    public function hasType(string $typeId): bool;

    /**
     * True when the general catch-all is the only registered type (it is then labelled "all
     * notifications" rather than "everything else").
     */
    public function hasOnlyGeneralType(): bool;

    /**
     * Whether any registered type may leave the application; when none can, no transport
     * channel is offered anywhere.
     */
    public function hasExternallyDeliverableType(): bool;

    /**
     * The type governing a stored notification. Unknown, empty or the legacy `info` resolve to
     * the general catch-all — which is what gives every notification ever written a pop-up
     * preference without touching its producer.
     */
    public function resolveBucket(?string $typeId): NotificationType;
}
