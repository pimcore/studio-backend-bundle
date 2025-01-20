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

namespace Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;

/**
 * @internal
 */
final readonly class SimpleSearchParameter extends CollectionParameters
{
    public function __construct(
        int $page = 1,
        int $pageSize = 50,
        private ?string $searchTerm = null,
    ) {
        parent::__construct($page, $pageSize);
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }
}
