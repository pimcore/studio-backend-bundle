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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;

/**
 * @internal
 */
final class KeyBindingHydrator implements KeyBindingHydratorInterface
{
    public function hydrate(array $data): array
    {
        $keyBindings = [];
        foreach ($data as $keyBinding) {
            $keyBindings[] = new KeyBinding(
                key: $keyBinding['key'],
                action: $keyBinding['action'],
                ctrl: $keyBinding['ctrl'],
                alt: $keyBinding['alt'],
                shift: $keyBinding['shift'],
            );
        }

        return $keyBindings;
    }
}
