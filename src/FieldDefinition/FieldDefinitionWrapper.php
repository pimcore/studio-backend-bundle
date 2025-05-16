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


namespace Pimcore\Bundle\StudioBackendBundle\FieldDefinition;



use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
final readonly class FieldDefinitionWrapper
{
    public function __construct(
        private Data $fieldDefinition,
        private string $containerType,
        private string $fieldname,
        private ?string $subContainerType = null,
        private ?string $subContainerKey = null
    )
    {
    }

    public function getFieldDefinition(): Data
    {
        return $this->fieldDefinition;
    }

    public function getContainerType(): string
    {
        return $this->containerType;
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function getSubContainerType(): ?string
    {
        return $this->subContainerType;
    }

    public function getSubContainerKey(): ?string
    {
        return $this->subContainerKey;
    }
}