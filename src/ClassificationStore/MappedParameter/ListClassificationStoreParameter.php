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
        private ?int $objectId = null,
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

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
