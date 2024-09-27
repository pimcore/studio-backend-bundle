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

namespace Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final readonly class NoteElementParameters
{
    public function __construct(
        private ?string $type = null,
        private ?int $id = null,

    ) {
        $this->validate();
    }

    public function getType(): ?string
    {
        if ($this->type === ElementTypes::TYPE_DATA_OBJECT) {
            return ElementTypes::TYPE_OBJECT;
        }

        return $this->type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function validate(): void
    {
        if (!in_array($this->type, ElementTypes::ALLOWED_TYPES, true)) {
            throw new InvalidElementTypeException($this->type);
        }
    }
}
