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

namespace Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class RenderAreaBlockParameter
{
    public function __construct(
        #[NotBlank(message: 'Name is required')]
        private string $name,
        #[NotBlank(message: 'RealName is required')]
        private string $realName,
        private int $index,
        private array $blockStateStack = [],
        private array $areaBlockConfig = [],
        private array $areaBlockData = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getBlockStateStack(): array
    {
        return $this->blockStateStack;
    }

    public function getAreaBlockConfig(): array
    {
        return $this->areaBlockConfig;
    }

    public function getAreaBlockData(): array
    {
        return $this->areaBlockData;
    }
}
