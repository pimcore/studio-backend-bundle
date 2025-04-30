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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;

#[Schema(
    title: 'Update User Role',
    description: 'Contains all information about a role that can be updated.',
    required: [
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
        'perspectives',
    ],
    type: 'object'
)]
final readonly class UpdateRole
{
    public function __construct(
        #[Property(description: 'Name of Folder or Role', type: 'string', example: 'admin')]
        private ?string $name,
        #[Property(description: 'Classes the user is allows to see', type: 'object', example: ['CAR'])]
        private array $classes,
        #[Property(type: 'int', example: '2')]
        private ?int $parentId,
        #[Property(description: 'List of permissions for the user', type: 'object', example: ['objects', 'documents'])]
        private array $permissions,
        #[Property(description: 'List of document types for the role', type: 'object', example: ['1', '2'])]
        private array $docTypes,
        #[Property(type: 'object', example: ['de', 'en'])]
        private array $websiteTranslationLanguagesEdit,
        #[Property(type: 'object', example: ['de'])]
        private array $websiteTranslationLanguagesView,
        #[Property(description: 'Asset Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $assetWorkspaces,
        #[Property(description: 'Data Object Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $dataObjectWorkspaces,
        #[Property(description: 'Document Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $documentWorkspaces,
        #[Property(
            description: 'Allowed studio perspectives',
            type: 'object',
            example: [Perspectives::DEFAULT_ID->value, 'some_otherPerspective_Id']
        )]
        private array $perspectives = [],
    ) {
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

    /**
     * @return string[]
     */
    public function getPerspectives(): array
    {
        return $this->perspectives;
    }
}
