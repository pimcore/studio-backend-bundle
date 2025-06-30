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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Repository;

use Pimcore\Bundle\SeoBundle\Model\Redirect\Listing;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;

/**
 * @internal
 */
interface RedirectsRepositoryInterface
{
    public function getListing(FilterParameter $parameters): Listing;
}
