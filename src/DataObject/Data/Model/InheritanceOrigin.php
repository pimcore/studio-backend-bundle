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
 * The object in an inheritance chain that holds a non-empty value for a field, together with that value.
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
