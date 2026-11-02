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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;

/**
 * Reads the values of calculated fields a data object version stores (Pimcore 2026.4+ with
 * `pimcore.objects.versions.calculated_value_snapshot` enabled, see Concrete::__getCalculatedValueSnapshot()).
 * Only an object restored from such a version holds them; the context identifies the field the way the
 * generated getters of the class do (object-level fields carry no owner name, localized fields the language).
 *
 * @internal
 */
interface CalculatedValueSnapshotServiceInterface
{
    public function hasStoredValue(Concrete $object, CalculatedValue $context): bool;

    public function getStoredValue(Concrete $object, CalculatedValue $context): mixed;
}
