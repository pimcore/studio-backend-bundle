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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class ListClassificationStoreParameter implements CollectionParametersInterface
{
    public function __construct(
        #[NotBlank(message: 'The store id must not be empty.')]
        private int $storeId,
        #[NotBlank(message: 'The field name must not be empty.')]
        private string $fieldName,
        #[NotBlank(message: 'The page name must not be empty.')]
        private int $page,
        #[NotBlank(message: 'The page size name must not be empty.')]
        private int $pageSize,
        private ?string $classId = null,
        private ?string $searchTerm = null,
    ) {
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }
}
