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

namespace Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter;

use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @internal
 */
final readonly class RenderletParameter
{
    public function __construct(
        #[NotBlank]
        #[Positive]
        private int $id,
        #[NotBlank(message: 'Element type is required')]
        private string $type,
        #[NotBlank(message: 'Controller is required')]
        private string $controller,
        private ?int $parentDocumentId = null,
        private ?string $template = null
    ) {
        if (!in_array($this->type, ElementTypes::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Invalid element type provided');
        }
    }

    public function getElementId(): int
    {
        return $this->id;
    }

    public function getElementType(): string
    {
        return $this->type;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getParentDocumentId(): ?int
    {
        return $this->parentDocumentId;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function toArray(): array
    {
        return [
            'elementId' => $this->getElementId(),
            'elementType' => $this->getElementType(),
            'controller' => $this->getController(),
            'parentDocumentId' => $this->getParentDocumentId(),
            'template' => $this->getTemplate(),
        ];
    }
}
