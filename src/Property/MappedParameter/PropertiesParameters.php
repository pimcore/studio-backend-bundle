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

namespace Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;

/**
 * @internal
 */
final readonly class PropertiesParameters
{
    public function __construct(
        private ?string $elementType = null,
        private ?string $filter = null

    ) {
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }

    public function getElementType(): ?string
    {
        if ($this->elementType === ElementTypes::TYPE_DATA_OBJECT) {
            return ElementTypes::TYPE_OBJECT;
        }

        return $this->elementType;
    }
}
