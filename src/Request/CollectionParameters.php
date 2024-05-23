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

namespace Pimcore\Bundle\StudioBackendBundle\Request;

use Pimcore\ValueObject\Integer\PositiveInteger;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

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

    private function validate(): void
    {
        new PositiveInteger($this->page);
        new PositiveInteger($this->pageSize);
    }
}
