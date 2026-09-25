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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type;

use const PHP_INT_MAX;

/**
 * The catch-all for notifications with an unknown, empty or legacy `info` type. Locked on, so
 * no preference can make a notification silently vanish (pop-up is the only control), and
 * never delivered externally — a type that wants that should describe itself properly.
 *
 * Built directly by the registry, never contributed by a provider: its presence must not
 * depend on wiring.
 *
 * @internal
 */
final readonly class GeneralNotificationType
{
    public const string TYPE_ID = 'info';

    /**
     * Labels used when this is the only registered type: it is then simply "all notifications",
     * not "everything else".
     */
    public const string SOLO_TRANSLATION_KEY = 'notifications.type.general.solo_label';

    public const string SOLO_DESCRIPTION_KEY = 'notifications.type.general.solo_description';

    public static function create(): NotificationType
    {
        return new NotificationType(
            typeId: self::TYPE_ID,
            translationKey: 'notifications.type.general.label',
            descriptionKey: 'notifications.type.general.description',
            group: 'general',
            sortOrder: PHP_INT_MAX, // always last: it is the residual bucket
            subscriptionLocked: true,
        );
    }
}
