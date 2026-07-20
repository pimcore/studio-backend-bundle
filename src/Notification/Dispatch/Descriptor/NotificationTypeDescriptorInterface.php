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
 * are collected automatically.
 *
 * Note the deliberate absence of a "supported channels" list. A descriptor declares only
 * WHETHER it may leave the application, never through which channel — otherwise a bundle
 * shipping a new channel could never make existing types support it without editing them.
 */
#[AutoconfigureTag(NotificationTypeDescriptorInterface::TAG)]
interface NotificationTypeDescriptorInterface
{
    public const string TAG = 'pimcore.studio_backend.notification_type';

    /**
     * Dotted, stable, and at most 20 characters — the `notifications`.`type` column is
     * VARCHAR(20) and MySQL truncates silently outside strict mode. Persisted in both the
     * notification row and the subscription row, so renaming one is a breaking change.
     */
    public function getTypeId(): string;

    public function getTranslationKey(): string;

    public function getDescriptionKey(): string;

    /**
     * Grouping key for the preferences screen, e.g. `tasks_discussions`.
     */
    public function getGroup(): string;

    /**
     * Drives both group order and order within a group. Never rely on service tag iteration
     * order, which is not stable across container rebuilds.
     */
    public function getSortOrder(): int;

    /**
     * Whether this type may be delivered outside the application at all. False means no
     * transport channel is ever offered for it, however many are registered.
     */
    public function allowsExternalDelivery(): bool;

    /**
     * Channel ids enabled when the user has expressed no preference. Separate axis from
     * capability: a channel registered later is available but stays off until chosen.
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
