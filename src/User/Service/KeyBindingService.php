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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\KeyBindingHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Psr\Log\LoggerInterface;
use function ord;

/**
 * @internal
 */
final class KeyBindingService implements KeyBindingServiceInterface
{
    public function __construct(
        private array $defaultKeyBindings,
        private readonly KeyBindingHydratorInterface $keyBindingHydrator,
        private readonly LoggerInterface $pimcoreLogger
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

    public function hydrateKeyBindings(?string $keyBindings): array
    {
        $bindings = [];

        if (!$keyBindings) {
            return $bindings;
        }

        try {
            $decoded = json_decode($keyBindings, true, 512, JSON_THROW_ON_ERROR);

            return $this->keyBindingHydrator->hydrate($decoded);
        } catch (Exception $e) {
            $this->pimcoreLogger->warning('Failed to decode key bindings', ['exception' => $e]);

            return [];
        }
    }

    private function convertKeyToAscii(): void
    {
        foreach ($this->defaultKeyBindings as $keyName => $keyValue) {

            $this->defaultKeyBindings[$keyName]['key'] = ord($keyValue['key']);
        }
    }
}
