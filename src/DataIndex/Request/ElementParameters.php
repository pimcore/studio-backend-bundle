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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Request;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ExcludeFolderParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\IdSearchParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ParentIdParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PathParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PqlParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\UserParameterInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
readonly class ElementParameters extends CollectionParameters implements
    ParentIdParameterInterface,
    IdSearchParameterInterface,
    ExcludeFolderParameterInterface,
    PathParameterInterface,
    UserParameterInterface,
    PqlParameterInterface
{
    public function __construct(
        int $page = 1,
        int $pageSize = 10,
        private ?int $parentId = null,
        private ?string $idSearchTerm = null,
        private ?string $pqlQuery = null,
        private bool $excludeFolders = false,
        private ?string $path = null,
        private bool $pathIncludeParent = false,
        private bool $pathIncludeDescendants = false,
        private ?UserInterface $user = null
    ) {
        parent::__construct($page, $pageSize);
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getIdSearchTerm(): ?string
    {
        return $this->idSearchTerm;
    }

    public function getPqlQuery(): ?string
    {
        return $this->pqlQuery;
    }

    public function getExcludeFolders(): bool
    {
        return $this->excludeFolders;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getPathIncludeParent(): bool
    {
        return $this->pathIncludeParent;
    }

    public function getPathIncludeDescendants(): bool
    {
        return $this->pathIncludeDescendants;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
}
