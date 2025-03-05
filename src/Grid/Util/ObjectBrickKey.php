<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

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
