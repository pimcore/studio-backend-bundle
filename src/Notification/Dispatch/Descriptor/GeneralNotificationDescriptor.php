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
 * The catch-all for notifications with an unknown, empty or legacy `info` type. Locked on, so
 * no preference can make a notification silently vanish (pop-up is the only control), and
 * never delivered externally — a type that wants that should describe itself properly.
 *
 * @internal
 */
final class GeneralNotificationDescriptor extends AbstractNotificationTypeDescriptor
{
    private const string TYPE_ID = 'info';

    private const string GROUP = 'general';

    private const string TRANSLATION_KEY = 'notifications.type.general.label';

    private const string DESCRIPTION_KEY = 'notifications.type.general.description';

    private const string SOLO_TRANSLATION_KEY = 'notifications.type.general.solo_label';

    private const string SOLO_DESCRIPTION_KEY = 'notifications.type.general.solo_description';

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
     * Label used when this is the only registered type ("all notifications").
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

    // always last: it is the residual bucket
    public function getSortOrder(): int
    {
        return PHP_INT_MAX;
    }

    public function isSubscriptionLocked(): bool
    {
        return true;
    }
}
