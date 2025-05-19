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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\Folder;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Class in data object folder',
    required: [
        'id',
        'name',
        'inheritance',
    ],
    type: 'object'
)]
class ClassDefinitionFolderItem implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'ID of class',
            type: 'string',
            example: 'NE'
        )]
        private readonly string $id,
        #[Property(
            description: 'Name of class',
            type: 'string',
            example: 'News'
        )]
        private readonly string $name,
        #[Property(
            description: 'Inheritance allowed',
            type: 'boolean',
            example: 'true'
        )]
        private readonly bool $inheritance,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isInheritance(): bool
    {
        return $this->inheritance;
    }
}
