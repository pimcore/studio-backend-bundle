<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;

/**
 * @internal
 */
final readonly class NoteParameters extends CollectionParameters
{
    public function __construct(
        int $page = 1,
        int $pageSize = 50,
        private ?string $sortBy = null,
        private ?string $sortOrder = null,
        private ?string $filter = null,
        private ?string $fieldFilters = null,
    ) {
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

    /**
     * @throws JsonException
     */
    public function getFieldFilters(): ?array
    {
        return $this->fieldFilters === null ? null :
            json_decode(
                $this->fieldFilters,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
    }
}
