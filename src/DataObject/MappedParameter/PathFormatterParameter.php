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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class PathFormatterParameter
{
    public function __construct(
        #[NotBlank(message: 'The object id must not be empty.')]
        private int $objectId,
        private array $targets,
        private string $fieldName,

    ) {
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getTargets(): array
    {
        return $this->targets;
    }
}
