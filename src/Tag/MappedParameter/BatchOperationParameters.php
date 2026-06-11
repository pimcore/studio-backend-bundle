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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter;

use Pimcore\Bundle\GenericDataIndexBundle\Exception\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Tag\Util\Constant\BatchOperations;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateElementTypeTrait;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class BatchOperationParameters
{
    use ValidateElementTypeTrait;

    public function __construct(
        #[NotBlank]
        private string $type,
        #[NotBlank]
        #[Positive]
        private int $id,
        #[NotBlank]
        private string $operation,
    ) {
        $this->validate($this->type);
        $this->validateOperation($operation);
    }

    public function getType(): string
    {
        return $this->mapElementType($this->type);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    private function validateOperation(string $operation): void
    {
        if (!in_array($operation, BatchOperations::values(), true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid operation "%s" provided.', $operation)
            );
        }
    }
}
