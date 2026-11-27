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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Model;

/**
 * A relation of a version to an element that was deleted afterwards.
 *
 * @internal
 */
final readonly class DeletedRelationReference
{
    /**
     * @param string|null $language language of a field inside the localized fields, null for any other field
     * @param int|null $index position in the stored list of a many-to-many relation, null for a single relation
     */
    public function __construct(
        private string $fieldName,
        private ?string $language,
        private ?int $index,
        private string $elementType,
        private int $elementId
    ) {
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }
}
