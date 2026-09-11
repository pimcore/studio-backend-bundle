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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model;

use Pimcore\Model\DataObject\Concrete;

/**
 * The result of walking an inheritance chain for a field: normally the nearest object holding a
 * non-empty value, together with that value - but when none of them does, the terminal ancestor with
 * its empty value, so objectId/inherited can still be reported without an inherited value being implied.
 *
 * @internal
 */
final readonly class InheritanceOrigin
{
    public function __construct(
        private Concrete $object,
        private mixed $value,
        private ?FieldContextData $contextData = null
    ) {
    }

    public function getObject(): Concrete
    {
        return $this->object;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getContextData(): ?FieldContextData
    {
        return $this->contextData;
    }
}
