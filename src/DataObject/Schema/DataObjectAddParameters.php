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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Model\DataObject\AbstractObject;

#[Schema(
    title: 'Add Data Object Parameters',
    required: ['key', 'classId', 'type'],
    type: 'object'
)]
final readonly class DataObjectAddParameters
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'my_new_data_object')]
        private string $key,
        #[Property(description: 'Class Id', type: 'string', example: 'data_object_class_id')]
        private string $classId,
        #[Property(
            description: 'Type',
            type: 'enum',
            enum: [AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_VARIANT],
            example: AbstractObject::OBJECT_TYPE_OBJECT
        )]
        private string $type,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getClassId(): string
    {
        return $this->classId;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
