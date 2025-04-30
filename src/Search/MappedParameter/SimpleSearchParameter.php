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
