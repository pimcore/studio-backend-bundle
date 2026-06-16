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

namespace Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Filter;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class SavedSearchParameter
{
    public function __construct(
        #[NotBlank]
        private string $name,
        private array $columns = [],
        private ?Filter $filters = null,
        private ?string $description = null,
        private ?string $classId = null,
        private bool $shareGlobal = false,
        private bool $createMenuShortcut = false,
        private array $sharedUsers = [],
        private array $sharedRoles = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilters(): ?Filter
    {
        return $this->filters;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function shareGlobal(): bool
    {
        return $this->shareGlobal;
    }

    public function createMenuShortcut(): bool
    {
        return $this->createMenuShortcut;
    }

    public function getSharedUsers(): array
    {
        return $this->sharedUsers;
    }

    public function getSharedRoles(): array
    {
        return $this->sharedRoles;
    }
}
