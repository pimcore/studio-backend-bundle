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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;

/**
 * @internal
 */
final readonly class SelectOptionHydrator implements SelectOptionHydratorInterface
{
    public function hydrate(array $data): SelectOption
    {
        return new SelectOption(
            key: $data['key'],
            value: $data['value']
        );
    }
}
