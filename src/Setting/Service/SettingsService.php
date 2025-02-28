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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use function array_key_exists;
use function sprintf;

/**
 * @internal
 */
final readonly class SettingsService implements SettingsServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private SettingProviderLoaderInterface $settingProviderLoader
    ) {
    }

    public function getSettings(): array
    {
        $settings = [];
        foreach ($this->settingProviderLoader->loadSettingProviders() as $settingProvider) {
            $settings = [
                ... $settings,
                ... $settingProvider->getSettings(),
            ];
        }

        return $settings;
    }

    /**
     * @throws InvalidElementTypeException
     */
    public function getTreePageSize(string $elementType): int
    {
        $settings = $this->getSettings();
        $elementType = $this->getCoreElementType($elementType);
        $settingKey = $elementType . '_tree_paging_limit';
        if (!array_key_exists($settingKey, $settings)) {
            throw new InvalidElementTypeException(
                sprintf('No tree paging limit setting found for element type "%s"', $elementType)
            );
        }

        return $settings[$settingKey];
    }
}
