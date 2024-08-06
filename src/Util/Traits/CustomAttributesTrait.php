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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

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
