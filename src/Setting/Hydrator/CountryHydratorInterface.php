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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\Country;

/**
 * @internal
 */
interface CountryHydratorInterface
{
    public function hydrate(string $name, string $code): Country;
}

