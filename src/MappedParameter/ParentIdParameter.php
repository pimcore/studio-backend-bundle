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

namespace Pimcore\Bundle\StudioBackendBundle\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ParentIdParameterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * @internal
 */
final readonly class ParentIdParameter implements ParentIdParameterInterface
{
    public function __construct(
        #[PositiveOrZero]
        #[NotBlank]
        private int $parentId = 1
    ) {
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }
}
