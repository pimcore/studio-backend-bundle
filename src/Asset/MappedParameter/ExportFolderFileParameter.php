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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Schema\ExportAllFilter;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final readonly class ExportFolderFileParameter
{
    /**
     * @param array<int> $folders
     */
    public function __construct(
        private array $folders,
        private ?FilterParameter $filters,
    ) {
        $this->validate();
    }

    /** @return array<int, ElementDescriptor> */
    public function getFolders(): array
    {
        return array_map(
            static fn (int $id) => new ElementDescriptor(ElementTypes::TYPE_ASSET, $id),
            $this->folders
        );
    }

    public function getFilters(): FilterParameter
    {
        return $this->filters ?? new FilterParameter();
    }

    private function validate(): void
    {
        if (empty($this->getFolders())) {
            throw new InvalidArgumentException('No folders provided');
        }
    }
}
