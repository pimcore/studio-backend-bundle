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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Factory;

use Pimcore\Bundle\StudioBackendBundle\Exception\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Property\Predefined;

final class PropertyFactory implements PropertyFactoryInterface
{
    /**
     * @throws NotWriteableException
     */
    public function create(): Predefined
    {
        if (!(new Predefined())->isWriteable()) {
            throw new NotWriteableException('Predefined Property');
        }

        $property = Predefined::create();
        $property->setCtype(ElementTypes::TYPE_DOCUMENT);
        $property->setName('New Property');
        $property->setKey('new_key');
        $property->setType('text');
        $property->save();

        return $property;
    }
}
