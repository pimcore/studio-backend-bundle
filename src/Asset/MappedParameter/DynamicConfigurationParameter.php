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

use JsonException;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class DynamicConfigurationParameter extends BasicStreamConfigParameter
{
    private array $configArray;

    /**
     * @throws JsonException
     */
    public function __construct(
        #[NotBlank(message: 'Configuration is required')]
        private string $config,
    ) {
        $this->configArray = json_decode($this->config, true, 512, JSON_THROW_ON_ERROR);

        parent::__construct(
            mimeType: $this->configArray['mimeType'] ?? null,
            cropPercent: $this->configArray['cropPercent'] ?? false,
            cropHeight: $this->configArray['cropHeight'] ?? null,
            cropWidth: $this->configArray['cropWidth'] ?? null,
            cropTop: $this->configArray['cropTop'] ?? null,
            cropLeft: $this->configArray['cropLeft'] ?? null,
        );
    }

    public function getDynamicConfig(): array
    {
        return $this->configArray;
    }
}
