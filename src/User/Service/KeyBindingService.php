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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\KeyBindingHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use function ord;

/**
 * @internal
 */
final class KeyBindingService implements KeyBindingServiceInterface
{
    public function __construct(
        private array $defaultKeyBindings,
        private readonly KeyBindingHydratorInterface $keyBindingHydrator
    ) {
    }

    /**
     * @return KeyBinding[]
     */
    public function getDefaultKeyBindings(): array
    {
        $this->convertKeyToAscii();

        return $this->keyBindingHydrator->hydrate($this->defaultKeyBindings);
    }

    private function convertKeyToAscii(): void
    {
        foreach ($this->defaultKeyBindings as $keyName => $keyValue) {

            $this->defaultKeyBindings[$keyName]['key'] = ord($keyValue['key']);
        }
    }
}
