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

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;

/**
 * @internal
 */
final readonly class PatchFolderParameter extends DataParameter
{
    public function __construct(
        array $data,
        private ?FilterParameter $filters,
    ) {
        parent::__construct($data);
    }

    public function getFilters(): FilterParameter
    {
        return $this->filters ?? new FilterParameter();
    }
}
