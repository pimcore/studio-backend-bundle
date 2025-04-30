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

namespace Pimcore\Bundle\StudioBackendBundle\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use function in_array;

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
        private int $id,
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
     * @throws InvalidElementTypeException
     */
    private function validate(): void
    {
        if (!in_array($this->type, ElementTypes::ALLOWED_TYPES)) {
            throw new InvalidElementTypeException($this->type);
        }
    }
}
