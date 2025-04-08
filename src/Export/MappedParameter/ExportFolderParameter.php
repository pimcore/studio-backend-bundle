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

namespace Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait\ExportConfigValidationTrait;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
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
        private string $elementType
    ) {
        if (empty($this->getFolders())) {
            throw new InvalidArgumentException('No folders provided');
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
}
