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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CsvSettings;
use stdClass;

/**
 * @internal
 */
final class CsvSettingsHydrator implements CsvSettingsHydratorInterface
{
    public function hydrate(stdClass $dialect): CsvSettings
    {
        return new CsvSettings(
            $dialect->delimiter ?? ';',
            $dialect->quotechar ?? '"',
            $dialect->escapechar ?? '\\',
            $dialect->lineterminator ?? ''
        );
    }
}
