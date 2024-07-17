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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomTreeAttributes;

/**
 * @internal
 */
trait CustomTreeAttributesTrait
{
    #[Property(description: 'Custom attributes for the tree', type: CustomTreeAttributes::class)]
    private ?CustomTreeAttributes $customTreeAttributes = null;

    public function getCustomTreeAttributes(): CustomTreeAttributes
    {
        if($this->customTreeAttributes === null){
            $this->customTreeAttributes = new CustomTreeAttributes();
        }

        return $this->customTreeAttributes;
    }

    public function setCustomTreeAttributes(CustomTreeAttributes $customTreeAttributes): void
    {
        $this->customTreeAttributes = $customTreeAttributes;
    }
}
