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
final readonly class ClassIdsParameters
{
    public function __construct(
        private string $classIds = ''
    ) {
    }

    public function getClassIds(): string
    {
        return $this->classIds;
    }

    /**
     * @return string[]
     */
    public function getClassIdsArray(): array
    {
        if (empty($this->classIds)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $this->classIds)),
            static fn (string $classId): bool => !empty($classId)
        );
    }
}
