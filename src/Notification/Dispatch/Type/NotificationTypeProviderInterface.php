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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contributes notification types. Any bundle may register one; tagged services are collected
 * automatically — typically a single provider returns all of a bundle's types.
 */
#[AutoconfigureTag(NotificationTypeProviderInterface::TAG)]
interface NotificationTypeProviderInterface
{
    public const string TAG = 'pimcore.studio_backend.notification_type_provider';

    /**
     * @return NotificationType[]
     */
    public function getTypes(): array;
}
