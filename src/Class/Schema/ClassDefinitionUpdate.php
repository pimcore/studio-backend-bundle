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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassDefinitionUpdate',
    title: 'Schema used to update class definition configuration',
    required: [
        'configuration',
        'values',
    ],
    type: 'object'
)]
final readonly class ClassDefinitionUpdate
{
    public function __construct(
        #[Property(
            description: 'Layout configuration for fields (Panel, Input, ..)',
            type: 'object',
            example: [
                'children' => [
                    [
                        'name' => 'Layout',
                        'datatype' => 'layout',
                        'fieldtype' => 'panel',
                        'type' => null,
                        'region' => null,
                        'title' => '',
                        'width' => '',
                        'height' => '',
                        'collapsible' => false,
                        'collapsed' => false,
                        'bodyStyle' => '',
                        'locked' => false,
                        'layout' => null,
                        'border' => false,
                        'icon' => '',
                        'labelWidth' => 100,
                        'labelAlign' => 'left',
                        'invalidFieldError' => null,
                        'children' => [],
                    ],
                ],
            ]
        )]
        private array $configuration,
        #[Property(
            description: 'Values for class definition object itself',
            type: 'object',
            example: [
                'name' => 'MyClass',
                'title' => 'My class title',
                'description' => 'Description of my class',
                'parentClass' => '',
                'implementsInterfaces' => '',
                'listingParentClass' => '',
                'useTraits' => '',
                'listingUseTraits' => '',
                'encryption' => false,
                'encryptedTables' => [],
                'allowInherit' => true,
                'allowVariants' => false,
                'showVariants' => false,
                'icon' => '',
                'group' => '',
                'showAppLoggerTab' => false,
                'linkGeneratorReference' => '',
                'previewGeneratorReference' => '',
                'compositeIndices' => [],
                'showFieldLookup' => false,
                'propertyVisibility' => [
                    'grid' => [
                        'id' => true,
                        'key' => false,
                        'path' => true,
                        'published' => true,
                        'modificationDate' => true,
                        'creationDate' => true,
                    ],
                    'search' => [
                        'id' => true,
                        'key' => false,
                        'path' => true,
                        'published' => true,
                        'modificationDate' => true,
                        'creationDate' => true,
                    ],
                ],
                'enableGridLocking' => false,
                'propertyVisibility.grid.id' => true,
                'propertyVisibility.search.id' => true,
                'propertyVisibility.grid.key' => false,
                'propertyVisibility.search.key' => false,
                'propertyVisibility.grid.path' => true,
                'propertyVisibility.search.path' => true,
                'propertyVisibility.grid.published' => true,
                'propertyVisibility.search.published' => true,
                'propertyVisibility.grid.modificationDate' => true,
                'propertyVisibility.search.modificationDate' => true,
                'propertyVisibility.grid.creationDate' => true,
                'propertyVisibility.search.creationDate' => true,
            ]
        )]
        private array $values
    ) {
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
