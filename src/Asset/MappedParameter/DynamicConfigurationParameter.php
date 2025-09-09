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

/**
 * @internal
 */
final readonly class DynamicConfigurationParameter extends BasicStreamConfigParameter
{
    public function __construct(
        private array $dynamicConfig,
    ) {
        parent::__construct(
            mimeType: $dynamicConfig['mimeType'] ?? null,
            cropPercent: $dynamicConfig['cropPercent'] ?? false,
            cropHeight: $dynamicConfig['cropHeight'] ?? null,
            cropWidth: $dynamicConfig['cropWidth'] ?? null,
            cropTop: $dynamicConfig['cropTop'] ?? null,
            cropLeft: $dynamicConfig['cropLeft'] ?? null,
        );
    }

    public function getDynamicConfig(): array
    {
        return $this->dynamicConfig;
    }

}
