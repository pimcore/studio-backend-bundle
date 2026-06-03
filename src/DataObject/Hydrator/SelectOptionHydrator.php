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
        // Options providers may return non-string values (e.g. an element id typed as int).
        // Pimcore persists select/multiselect values as strings, so the option value must be
        // serialized as a string as well; otherwise Studio UI cannot match the reloaded
        // string value against a numeric option value and shows the raw value instead of the
        // label. See https://github.com/pimcore/studio-ui-bundle/issues/3322
        return new SelectOption(
            key: $data['key'],
            value: (string) $data['value']
        );
    }
}
