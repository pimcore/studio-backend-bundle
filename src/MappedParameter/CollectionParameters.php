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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\ValueObject\Integer\PositiveInteger;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use ValueError;

/**
 * @internal
 */
readonly class CollectionParameters implements CollectionParametersInterface
{
    public function __construct(
        #[NotBlank]
        #[Positive]
        private int $page = 1,
        #[NotBlank]
        #[Positive]
        private int $pageSize = 10,
        private ?FilterParameter $filters = null,
    ) {
        $this->validate();
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }

    public function getFilters(): ?FilterParameter
    {
        return $this->filters;
    }

    private function validate(): void
    {
        try {
            new PositiveInteger($this->page);
            new PositiveInteger($this->pageSize);
        } catch (ValueError $e) {
            throw new InvalidFilterException($e->getMessage());
        }
    }
}
