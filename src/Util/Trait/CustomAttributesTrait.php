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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;

/**
 * @internal
 */
trait CustomAttributesTrait
{
    #[Property(description: 'Custom attributes for the tree', type: CustomAttributes::class)]
    private ?CustomAttributes $customAttributes = null;

    public function getCustomAttributes(): CustomAttributes
    {
        if ($this->customAttributes === null) {
            $this->customAttributes = new CustomAttributes();
        }

        return $this->customAttributes;
    }

    public function setCustomAttributes(CustomAttributes $customAttributes): void
    {
        $this->customAttributes = $customAttributes;
    }
}
