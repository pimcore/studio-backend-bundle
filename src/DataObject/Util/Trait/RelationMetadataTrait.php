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

use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
trait RelationMetadataTrait
{
    private function addRelationMetadata(
        ElementInterface $object,
        array $relationData,
        ObjectMetadata|ElementMetadata $metadata
    ): ObjectMetadata|ElementMetadata {
        $metadata->_setOwner($object);
        $metadata->_setOwnerFieldname($metadata->getFieldname());

        foreach ($metadata->getColumns() as $column) {
            $setter = 'set' . ucfirst($column);
            $value = $relationData[$column] ?? null;
            $metadata->$setter($value);
        }

        return $metadata;
    }
}
