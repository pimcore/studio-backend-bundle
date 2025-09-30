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

namespace Pimcore\Bundle\StudioBackendBundle\Role\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDataObjectWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDocumentWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class UpdateRoleParameter
{
    public function __construct(
        private array $classes,
        #[NotBlank(message: 'ParentId is required')]
        private int $parentId,
        private array $permissions,
        private array $docTypes,
        private array $websiteTranslationLanguagesEdit,
        private array $websiteTranslationLanguagesView,
        private array $assetWorkspaces,
        private array $dataObjectWorkspaces,
        private array $documentWorkspaces,
        private array $perspectives,
    ) {
    }

    public function getParentId(): int
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

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getDocTypes(): array
    {
        return $this->docTypes;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getAssetWorkspaces(): array
    {
        return $this->assetWorkspaces;
    }

    /**
     * @return UserDataObjectWorkspace[]
     */
    public function getDataObjectWorkspaces(): array
    {
        return $this->dataObjectWorkspaces;
    }

    /**
     * @return UserDocumentWorkspace[]
     */
    public function getDocumentWorkspaces(): array
    {
        return $this->documentWorkspaces;
    }

    /**
     * @return string[]
     */
    public function getPerspectives(): array
    {
        return $this->perspectives;
    }
}
