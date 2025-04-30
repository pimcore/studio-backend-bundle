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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class ConvertAllUnitsParameter
{
    public function __construct(
        #[NotBlank]
        private string $fromUnitId,
        #[NotBlank]
        private float|int $value,
    ) {
    }

    public function getFromUnitId(): string
    {
        return $this->fromUnitId;
    }

    public function getValue(): float|int
    {
        return $this->value;
    }
}
