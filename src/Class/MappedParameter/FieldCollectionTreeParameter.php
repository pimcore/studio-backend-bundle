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
final readonly class FieldCollectionTreeParameter
{
    public function __construct(
        private ?string $allowedTypes = null,
    ) {
    }

    /**
     * @return string[]|null
     */
    public function getAllowedTypesArray(): ?array
    {
        if ($this->allowedTypes === null) {
            return null;
        }

        $types = array_filter(
            array_map('trim', explode(',', $this->allowedTypes)),
            static fn (string $type): bool => $type !== ''
        );

        return $types !== [] ? $types : null;
    }
}
