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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Detailed User Role',
    description: 'Contains all information about a role',
    required: [
        'id',
        'name',
        'classes',
        'parentId',
        'permissions',
        'docTypes',
        'websiteTranslationLanguagesEdit',
        'websiteTranslationLanguagesView',
        'assetWorkspaces',
        'dataObjectWorkspaces',
        'documentWorkspaces',
    ],
    type: 'object'
)]
final class DetailedRole implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the User', type: 'integer', example: '1')]
        private readonly int $id,
        #[Property(description: 'Name of Folder or Role', type: 'string', example: 'admin')]
        private readonly ?string $name,
        #[Property(description: 'Classes the user is allows to see', type: 'object', example: ['CAR'])]
        private readonly array $classes,
        #[Property(type: 'int', example: '2')]
        private readonly ?int $parentId,
        #[Property(description: 'List of permissions for the user', type: 'object', example: ['objects', 'documents'])]
        private readonly array $permissions,
        #[Property(description: 'List of document types for the role', type: 'object', example: ['1', '2'])]
        private readonly array $docTypes,
        #[Property(type: 'object', example: ['de', 'en'])]
        private readonly array $websiteTranslationLanguagesEdit,
        #[Property(type: 'object', example: ['de'])]
        private readonly array $websiteTranslationLanguagesView,
        #[Property(description: 'Asset Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private readonly array $assetWorkspaces,
        #[Property(description: 'Data Object Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private readonly array $dataObjectWorkspaces,
        #[Property(description: 'Document Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private readonly array $documentWorkspaces,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getWebsiteTranslationLanguagesEdit(): array
    {
        return $this->websiteTranslationLanguagesEdit;
    }

    public function getWebsiteTranslationLanguagesView(): array
    {
        return $this->websiteTranslationLanguagesView;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getAssetWorkspaces(): array
    {
        return $this->assetWorkspaces;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getDataObjectWorkspaces(): array
    {
        return $this->dataObjectWorkspaces;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getDocumentWorkspaces(): array
    {
        return $this->documentWorkspaces;
    }

    public function getDocTypes(): array
    {
        return $this->docTypes;
    }
}
