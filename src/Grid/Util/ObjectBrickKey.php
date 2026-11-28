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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

use function count;
use function explode;

/**
 * @internal
 */
final readonly class ObjectBrickKey
{
    public function __construct(
        private string $field,
        private string $brickName,
        private string $attribute
    ) {
    }

    /**
     * Returns null unless the key has the format <field>.<brickname>.<attributeofbrick>.
     */
    public static function fromColumnKey(string $key): ?self
    {
        $parts = explode('.', $key);
        if (count($parts) !== 3) {
            return null;
        }

        return new self(
            field: $parts[0],
            brickName: $parts[1],
            attribute: $parts[2]
        );
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getBrickName(): string
    {
        return $this->brickName;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }
}
