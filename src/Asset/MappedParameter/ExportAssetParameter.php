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
final readonly class ExportAssetParameter extends ExportParameter
{
    /**
     * @param array<int> $assets
     */
    public function __construct(
        array $columns,
        array $config,
        private array $assets
    )
    {
        parent::__construct($columns, new FilterParameter(), $config);
        $this->validate();
    }

    /** @return array<int, ElementDescriptor> */
    public function getAssets(): array
    {
        return array_map(
            static fn (int $id) => new ElementDescriptor(ElementTypes::TYPE_ASSET, $id),
            $this->assets
        );
    }

    private function validate(): void
    {
        if (empty($this->getAssets())) {
            throw new InvalidArgumentException('No assets provided');
        }
    }
}
