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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveAssetContextPermissions as APermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDataObjectContextPermissions as DOPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDocumentContextPermissions as DPermissions;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
#[Schema(
    title: 'Create Element Tree Widget Config',
    required: [
        'id',
        'name',
        'icon',
        'contextPermissions',
        'elementType',
        'rootFolder',
        'showRoot',
        'classes',
        'pql',
        'pageSize',
    ],
    type: 'object'
)]
final readonly class SaveElementTreeWidgetConfig
{
    public function __construct(
        #[Property(description: 'Widget ID', type: 'string', example: '5026c239_eb75_499a_8576_841bca283350')]
        private string $id,
        #[Property(description: 'Name', type: 'string', example: 'Cars')]
        private string $name,
        #[Property(description: 'Icon', type: ElementIcon::class)]
        private ElementIcon $icon,
        #[Property(
            description: 'Context Permissions',
            type: 'object',
            oneOf: [
                new Schema(APermissions::class),
                new Schema(DOPermissions::class),
                new Schema(DPermissions::class),
            ]
        )]
        private APermissions|DOPermissions|DPermissions $contextPermissions,
        #[Property(description: 'Element Type', type: 'string', example: ElementTypes::TYPE_DATA_OBJECT)]
        private string $elementType = ElementTypes::TYPE_DATA_OBJECT,
        #[Property(description: 'Root Folder', type: 'string', example: '/Product Data/Cars')]
        private string $rootFolder = '/',
        #[Property(description: 'Show Root', type: 'bool', example: false)]
        private bool $showRoot = false,
        #[Property(description: 'Classes', type: 'object', example: ['CAR'])]
        private array $classes = [],
        #[Property(description: 'PQL', type: 'string', example: null)]
        private ?string $pql = null,
        #[Property(description: 'Page size', type: 'int', example: 20)]
        private ?int $pageSize = null,
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

    public function getIcon(): ElementIcon
    {
        return $this->icon;
    }

    public function getContextPermissions(): DPermissions|APermissions|DOPermissions
    {
        return $this->contextPermissions;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getRootFolder(): string
    {
        return $this->rootFolder;
    }

    public function isShowRoot(): bool
    {
        return $this->showRoot;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getPql(): ?string
    {
        return $this->pql;
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }
}
