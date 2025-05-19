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

namespace Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;

/**
 * @internal
 */
final readonly class NoteParameters extends CollectionParameters
{
    private array $fieldFiltersArray;

    /**
     * @throws JsonException
     */
    public function __construct(
        int $page = 1,
        int $pageSize = 50,
        private ?string $sortBy = null,
        private ?string $sortOrder = null,
        private ?string $filter = null,
        ?string $fieldFilters = null,
    ) {
        $this->fieldFiltersArray = $fieldFilters !== null ?
            json_decode($fieldFilters, true, 512, JSON_THROW_ON_ERROR) :
            [];

        parent::__construct($page, $pageSize);
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }

    public function getFieldFiltersArray(): ?array
    {
        return $this->fieldFiltersArray;
    }
}
