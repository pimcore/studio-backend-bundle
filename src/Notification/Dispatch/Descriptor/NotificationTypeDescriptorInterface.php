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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Describes a subscribable notification type. Any bundle may contribute one; tagged services
 * are collected automatically. Deliberately no "supported channels" list — a descriptor says
 * only WHETHER it may leave the application, so new channels light up for existing types.
 */
#[AutoconfigureTag(NotificationTypeDescriptorInterface::TAG)]
interface NotificationTypeDescriptorInterface
{
    public const string TAG = 'pimcore.studio_backend.notification_type';

    /**
     * Dotted, stable, at most 20 characters (the `notifications`.`type` column). Persisted in
     * notification and subscription rows, so renaming is a breaking change.
     */
    public function getTypeId(): string;

    public function getTranslationKey(): string;

    public function getDescriptionKey(): string;

    /**
     * Grouping key for the preferences screen, e.g. `tasks_discussions`.
     */
    public function getGroup(): string;

    /**
     * Drives group order and order within a group; tag iteration order is not stable.
     */
    public function getSortOrder(): int;

    /**
     * Whether this type may be delivered outside the application at all.
     */
    public function allowsExternalDelivery(): bool;

    /**
     * Channel ids enabled when the user has expressed no preference.
     *
     * @return string[]
     */
    public function getDefaultChannels(): array;

    public function isSubscribedByDefault(): bool;

    /**
     * True for types a user must not be able to switch off entirely.
     */
    public function isSubscriptionLocked(): bool;
}
