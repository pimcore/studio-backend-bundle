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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final readonly class ExportFolderParameter extends ExportParameter
{
    /**
     * @param array<int> $folders
     */
    public function __construct(
        array $columns,
        ?FilterParameter $filters,
        array $config,
        private array $folders
    ) {
        parent::__construct($columns, $filters, $config);
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

    private function validate(): void
    {
        if (empty($this->getFolders())) {
            throw new InvalidArgumentException('No folders provided');
        }
    }
}
