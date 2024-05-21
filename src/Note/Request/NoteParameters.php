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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Request;

use Pimcore\Bundle\StudioBackendBundle\Request\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;

/**
 * @internal
 */
final readonly class NoteParameters extends CollectionParameters
{
    public function __construct(
        private ?string $sortBy = null,
        private ?string $sortOrder = null,
        private ?string $filter = null,
        int $page = 1,
        int $pageSize = 50,
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
}
