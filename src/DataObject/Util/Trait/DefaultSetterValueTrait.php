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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
trait DefaultSetterValueTrait
{
    public function getDefaultDataForSetter(Concrete $element, Data $fieldDefinition, string $key, array $data): mixed
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        /** @var ResourcePersistenceAwareInterface $fieldDefinition */
        return $fieldDefinition->getDataFromResource($data[$key], $element);
    }
}
