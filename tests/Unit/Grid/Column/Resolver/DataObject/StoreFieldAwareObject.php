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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\DataObject;

use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;

/**
 * A data object whose class DOES carry the classification store field, the way a generated class
 * carries a real getter for it.
 *
 * The export read of one slot is guarded by `method_exists()`, the way core's own read of that slot
 * is guarded, so the guard has two halves and each needs a model of its own: this one for the half
 * that reads, and a plain `Concrete` - which carries no such getter - for the half where a saved
 * column names a field the class no longer has.
 *
 * @internal
 */
final class StoreFieldAwareObject extends Concrete
{
    public ?Classificationstore $classificationStore = null;

    public function getCs(): ?Classificationstore
    {
        return $this->classificationStore;
    }
}
