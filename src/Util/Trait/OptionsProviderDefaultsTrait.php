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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use function is_array;

/**
 * Fills in the defaults the Studio UI does not send for select-like field definitions.
 *
 * A field definition that references a select options configuration only carries
 * `optionsProviderType: select_options` and the configuration name in
 * `optionsProviderData`. The core resolves the options through
 * `optionsProviderClass`, so without it the field renders with no options at all.
 *
 * @internal
 */
trait OptionsProviderDefaultsTrait
{
    private function applyOptionsProviderDefaults(array $config): array
    {
        $type = $config['optionsProviderType'] ?? null;
        $class = $config['optionsProviderClass'] ?? null;

        if ($type === OptionsProviderInterface::TYPE_SELECT_OPTIONS && empty($class)) {
            $config['optionsProviderClass'] = SelectOptionsOptionsProvider::class;
        }

        if (isset($config['children']) && is_array($config['children'])) {
            foreach ($config['children'] as $key => $child) {
                if (is_array($child)) {
                    $config['children'][$key] = $this->applyOptionsProviderDefaults($child);
                }
            }
        }

        return $config;
    }
}
