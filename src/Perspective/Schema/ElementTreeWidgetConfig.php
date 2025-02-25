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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions as APermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DataObjectContextPermissions as DOPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions as DPermissions;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderIds;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
#[Schema(
    title: 'Element Tree Widget',
    required: [
        'contextPermissions',
        'elementType',
        'rootFolder',
        'rootFolderId',
        'showRoot',
        'classes',
        'pql',
        'pageSize',
        'isWriteable',
    ],
    type: 'object'
)]
final class ElementTreeWidgetConfig extends WidgetConfig
{
    public function __construct(
        string $id,
        string $name,
        #[Property(
            description: 'Context Permissions',
            type: 'object',
            oneOf: [
                new Schema(APermissions::class),
                new Schema(DOPermissions::class),
                new Schema(DPermissions::class),
            ]
        )]
        private readonly APermissions|DOPermissions|DPermissions $contextPermissions,
        ElementIcon $icon,
        #[Property(description: 'Element Type', type: 'string', example: ElementTypes::TYPE_DATA_OBJECT)]
        private readonly string $elementType = ElementTypes::TYPE_DATA_OBJECT,
        #[Property(description: 'Root Folder', type: 'string', example: '/Product Data/Cars')]
        private readonly string $rootFolder = ElementFolderPaths::ROOT->value,
        #[Property(description: 'Root Folder ID', type: 'int', example: 2)]
        private readonly int $rootFolderId = ElementFolderIds::ROOT->value,
        #[Property(description: 'Show Root', type: 'bool', example: false)]
        private readonly bool $showRoot = false,
        #[Property(description: 'Classes', type: 'object', example: ['CAR'])]
        private readonly array $classes = [],
        #[Property(description: 'PQL', type: 'string', example: null)]
        private readonly ?string $pql = null,
        #[Property(description: 'Page size', type: 'int', example: 20)]
        private readonly ?int $pageSize = null,
        #[Property(description: 'Is Writeable', type: 'bool', example: true)]
        private readonly bool $isWriteable = true,
    ) {
        parent::__construct($id, $name, WidgetTypes::ELEMENT_TREE->value, $icon);
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getRootFolder(): string
    {
        return $this->rootFolder;
    }

    public function getRootFolderId(): int
    {
        return $this->rootFolderId;
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

    public function getContextPermissions(): DPermissions|APermissions|DOPermissions
    {
        return $this->contextPermissions;
    }

    public function isWriteable(): bool
    {
        return $this->isWriteable;
    }
}
