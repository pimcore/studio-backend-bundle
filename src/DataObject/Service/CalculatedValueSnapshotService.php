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
use Pimcore\Model\DataObject\Service as DataObjectService;
use function array_key_exists;

/**
 * @internal
 */
final readonly class CalculatedValueSnapshotService implements CalculatedValueSnapshotServiceInterface
{
    public function hasStoredValue(Concrete $object, CalculatedValue $context): bool
    {
        $snapshot = $object->__getCalculatedValueSnapshot();

        // a stored null is a value too, only a missing entry means the version holds nothing for the field
        return $snapshot !== null
            && array_key_exists(DataObjectService::getCalculatedValueSnapshotKey($context), $snapshot);
    }

    public function getStoredValue(Concrete $object, CalculatedValue $context): mixed
    {
        $key = DataObjectService::getCalculatedValueSnapshotKey($context);

        return $object->__getCalculatedValueSnapshot()[$key] ?? null;
    }
}
