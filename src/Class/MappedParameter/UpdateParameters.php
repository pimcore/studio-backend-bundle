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

namespace Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter;

/**
 * @internal
 */
final readonly class UpdateParameters
{
    public function __construct(
        private array $configuration,
        private array $values
    ) {

    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
