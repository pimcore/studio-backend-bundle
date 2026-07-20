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

/**
 * The catch-all every notification falls into when its type is unknown, empty or the legacy
 * `info` default — workflow transitions, user-to-user messages, anything a bundle writes
 * directly through the notification model.
 *
 * Two deliberate properties:
 *  - Its subscription is locked on, so no preference can make a notification silently vanish.
 *    Pop-up is the only control, which is exactly the one that was missing.
 *  - It allows no external delivery. A bucket of unclassified notifications is not something
 *    to email; a type that wants that should describe itself properly.
 *
 * @internal
 */
final class GeneralNotificationDescriptor extends AbstractNotificationTypeDescriptor
{
    private const string TYPE_ID = 'info';

    private const string GROUP = 'general';

    private const string TRANSLATION_KEY = 'notification.type.general.label';

    private const string DESCRIPTION_KEY = 'notification.type.general.description';

    private const string SOLO_TRANSLATION_KEY = 'notification.type.general.solo_label';

    private const string SOLO_DESCRIPTION_KEY = 'notification.type.general.solo_description';

    public function getTypeId(): string
    {
        return self::TYPE_ID;
    }

    public function getTranslationKey(): string
    {
        return self::TRANSLATION_KEY;
    }

    public function getDescriptionKey(): string
    {
        return self::DESCRIPTION_KEY;
    }

    /**
     * Label used when this is the only registered type: there is then nothing for it to be
     * "everything else" to, so it presents as all notifications instead.
     */
    public function getSoloTranslationKey(): string
    {
        return self::SOLO_TRANSLATION_KEY;
    }

    public function getSoloDescriptionKey(): string
    {
        return self::SOLO_DESCRIPTION_KEY;
    }

    public function getGroup(): string
    {
        return self::GROUP;
    }

    /**
     * Always last: it is the residual bucket.
     */
    public function getSortOrder(): int
    {
        return PHP_INT_MAX;
    }

    public function isSubscriptionLocked(): bool
    {
        return true;
    }
}
