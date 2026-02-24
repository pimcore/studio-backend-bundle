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
    schema: 'ObjectBrickUpdate',
    title: 'Schema used to update object brick definition',
    required: [
        'configuration',
        'values',
    ],
    type: 'object'
)]
final readonly class ObjectBrickUpdate
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
                        'region' => '',
                        'title' => 'Pattern',
                        'width' => '',
                        'height' => '',
                        'collapsible' => false,
                        'collapsed' => false,
                        'bodyStyle' => '',
                        'locked' => false,
                        'layout' => '',
                        'border' => false,
                        'icon' => null,
                        'labelWidth' => 100,
                        'labelAlign' => 'left',
                        'invalidFieldError' => null,
                        'children' => [
                            [
                                'name' => 'count',
                                'datatype' => 'data',
                                'fieldtype' => 'numeric',
                                'title' => 'Token Count',
                                'tooltip' => '',
                                'mandatory' => false,
                                'noteditable' => false,
                                'index' => false,
                                'locked' => false,
                                'style' => '',
                                'permissions' => null,
                                'relationType' => false,
                                'invisible' => false,
                                'visibleGridView' => false,
                                'visibleSearch' => false,
                                'defaultValue' => null,
                                'integer' => true,
                                'unsigned' => true,
                                'minValue' => 1,
                                'maxValue' => null,
                                'unique' => false,
                                'decimalSize' => null,
                                'decimalPrecision' => null,
                                'width' => 400,
                                'defaultValueGenerator' => '',
                                'invalidFieldError' => null,
                                'children' => null,
                            ],
                        ],
                    ],
                ],
            ]
        )]
        private array $configuration,
        #[Property(
            description: 'Values for object brick definition metadata',
            type: 'object',
            example: [
                'parentClass' => '\\App\\Bundle\\Model\\MyCustomParentType',
                'implementsInterfaces' => '',
                'title' => '',
                'group' => 'Voucher',
                'classDefinitions' => [
                    ['classname' => 'Product', 'fieldname' => 'myBrickField'],
                ],
                'blockedVarsForExport' => [],
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
