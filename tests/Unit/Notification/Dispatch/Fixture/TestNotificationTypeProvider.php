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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture;

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationTypeProviderInterface;

/**
 * A registrable provider class for the compiler-pass test (tagging needs a real class name).
 */
final class TestNotificationTypeProvider implements NotificationTypeProviderInterface
{
    public function getTypes(): array
    {
        return [];
    }
}
