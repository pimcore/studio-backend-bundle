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
final readonly class FieldsByTypeParameters
{
    public function __construct(
        private string $classId,
        private string $type,
    ) {
    }

    public function getClassId(): string
    {
        return $this->classId;
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return array_filter(
            array_map('trim', explode(',', $this->type)),
            static fn (string $t): bool => !empty($t)
        );
    }
}
