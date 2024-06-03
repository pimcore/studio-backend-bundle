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

namespace Pimcore\Bundle\StudioBackendBundle\Request;

use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\ValueObject\Integer\PositiveInteger;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use ValueError;

/**
 * @internal
 */
final readonly class ElementParameters
{
    public function __construct(
        #[NotBlank]
        private string $type,
        #[NotBlank]
        #[Positive]
        private int    $id,
    ) {
        $this->validate();
    }

    public function getType(): string
    {
        if ($this->type === ElementTypes::TYPE_DATA_OBJECT) {
            return ElementTypes::TYPE_OBJECT;
        }
        return $this->type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws InvalidElementTypeException|ValueError
     */
    private function validate(): void
    {
        if (!in_array($this->type, ElementTypes::ALLOWED_TYPES)) {
            throw new InvalidElementTypeException($this->type);
        }

        new PositiveInteger($this->id);
    }
}
