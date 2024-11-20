<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
