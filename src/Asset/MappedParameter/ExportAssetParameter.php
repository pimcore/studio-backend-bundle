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

use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constants\Csv;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final readonly class ExportAssetParameter
{
    /**
     * @param array $assets
     * @param array $gridConfig
     * @param array $settings
     */
    public function __construct(
        private array $assets,
        private array $gridConfig,
        private array $settings,
    ) {
        $this->validate();
    }

    public function getGridConfig(): array
    {
        return $this->gridConfig;
    }

    public function getSettings(): array
    {
        return $this->settings;
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
        if (empty($this->assets)) {
            throw new InvalidArgumentException('No assets provided');
        }

        if (empty($this->gridConfig)) {
            throw new InvalidArgumentException('No grid config provided');
        }

        if (empty($this->settings)) {
            throw new InvalidArgumentException('No settings provided');
        }

        if (!isset($this->settings[Csv::SETTINGS_DELIMITER->value])) {
            throw new InvalidArgumentException('No delimiter provided');
        }
    }
}