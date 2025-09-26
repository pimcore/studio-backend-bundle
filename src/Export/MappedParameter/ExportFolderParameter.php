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

namespace Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait\ExportConfigValidationTrait;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final readonly class ExportFolderParameter
{
    use ExportConfigValidationTrait;

    /**
     * @param array<int> $folders
     */
    public function __construct(
        private array $columns,
        private ?FilterParameter $filters,
        private array $config,
        private array $folders,
        private string $elementType,
        private ?string $classId = null
    ) {
        if (empty($this->getFolders())) {
            throw new InvalidArgumentException('No folders provided');
        }

        if ($this->classId === null && $this->getValidElementType($this->elementType) === ElementTypes::TYPE_OBJECT) {
            throw new InvalidArgumentException('Class ID must be provided for data object exports');
        }

        $this->validateConfig();
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilters(): FilterParameter
    {
        return $this->filters ?? new FilterParameter();
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array<int, ElementDescriptor>
     */
    public function getFolders(): array
    {
        return array_map(
            fn (int $id) => new ElementDescriptor($this->getElementType(), $id),
            $this->folders
        );
    }

    public function getElementType(): string
    {
        return $this->getValidElementType($this->elementType);
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }
}
