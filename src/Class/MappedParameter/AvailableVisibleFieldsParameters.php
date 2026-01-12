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
final readonly class AvailableVisibleFieldsParameters
{
    public function __construct(
        private string $classNames = ''
    ) {
    }

    public function getClassNames(): string
    {
        return $this->classNames;
    }

    /**
     * @return string[]
     */
    public function getClassNamesArray(): array
    {
        if (empty($this->classNames)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $this->classNames)),
            static fn(string $className): bool => !empty($className)
        );
    }
}

