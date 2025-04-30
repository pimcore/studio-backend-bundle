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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Key Binding for a User',
    description: 'Key Binding for a User',
    required: ['key', 'action', 'ctrl', 'alt', 'shift'],
    type: 'object',
)]
final readonly class KeyBinding
{
    public function __construct(
        #[Property(description: 'ASCII Code for a key on the Keyboard', type: 'integer', example: '83')]
        private int $key,
        #[Property(description: 'The action the key binding shoudl execute', type: 'string', example: 'save')]
        private string $action,
        #[Property(description: 'If CTRL key should be pressed', type: 'boolean', example: 'true')]
        private bool $ctrl,
        #[Property(description: 'If ALT key should be pressed', type: 'boolean', example: 'true')]
        private bool $alt,
        #[Property(description: 'If SHIFT key should be pressed', type: 'boolean', example: 'true')]
        private bool $shift,
    ) {
    }

    public function getKey(): int
    {
        return $this->key;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getCtrl(): bool
    {
        return $this->ctrl;
    }

    public function getAlt(): bool
    {
        return $this->alt;
    }

    public function getShift(): bool
    {
        return $this->shift;
    }
}
