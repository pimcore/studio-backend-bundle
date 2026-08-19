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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;

/**
 * @internal
 */
interface NotificationTypeRegistryInterface
{
    /**
     * The `notifications`.`type` column is VARCHAR(20). Enforced when descriptors are
     * collected so an over-long id fails loudly at boot instead of being silently truncated
     * into a type that matches no descriptor.
     */
    public const int MAX_TYPE_ID_LENGTH = 20;

    /**
     * All registered descriptors, ordered by sort order then type id.
     *
     * @return NotificationTypeDescriptorInterface[]
     */
    public function getDescriptors(): array;

    /**
     * @throws NotFoundException
     */
    public function getDescriptor(string $typeId): NotificationTypeDescriptorInterface;

    public function hasDescriptor(string $typeId): bool;

    /**
     * True when the general catch-all is the only registered type. It is then labelled "all
     * notifications" rather than "everything else", because there is nothing else.
     */
    public function hasOnlyGeneralDescriptor(): bool;

    /**
     * Whether any registered type may leave the application. When none can, no transport
     * channel is offered anywhere and the preferences screen shows no channel columns.
     */
    public function hasExternallyDeliverableType(): bool;

    /**
     * Resolves a notification's stored type to the descriptor that governs it. An unknown,
     * empty or legacy `info` type resolves to the general catch-all, which is what gives
     * every notification ever written a pop-up preference without touching its producer.
     */
    public function resolveBucket(?string $typeId): NotificationTypeDescriptorInterface;
}
