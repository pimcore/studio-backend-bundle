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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldCollectionData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

/**
 * @internal
 */
final readonly class FieldContextData
{
    public function __construct(
        private AbstractData|BlockData|FieldCollectionData|Classificationstore|Localizedfield|Concrete|null $contextObject = null,
        private ?string $language = null,
        private ?int $classificationStoreGroupId = null,
        private ?int $classificationStoreKeyId = null,
        private array $legacyParameters = [],
        private bool $resolveInheritedValue = false,
        private ?string $containerFieldName = null,
        private ?string $containerBrickType = null
    ) {
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getContextObject(
    ): AbstractData|BlockData|FieldCollectionData|Classificationstore|Localizedfield|Concrete|null {
        return $this->contextObject;
    }

    public function getClassificationStoreGroupId(): ?int
    {
        return $this->contextObject instanceof Classificationstore ? $this->classificationStoreGroupId : null;
    }

    public function getClassificationStoreKeyId(): ?int
    {
        return $this->contextObject instanceof Classificationstore ? $this->classificationStoreKeyId : null;
    }

    /**
     * Whether inheritance data should also carry the (normalized) value inherited from the ancestors.
     * Opt-in, since resolving it costs an additional walk up the tree for every field holding an own value.
     */
    public function shouldResolveInheritedValue(): bool
    {
        return $this->resolveInheritedValue;
    }

    /**
     * Use to pass legacy parameters used in core adapters
     */
    public function getLegacyParameters(): array
    {
        return $this->legacyParameters;
    }

    /**
     * @throws Exception
     */
    public function getFieldValueFromContextObject(string $fieldName): mixed
    {
        $contextObject = $this->getContextObject();

        return match (true) {
            $contextObject instanceof AbstractData, $contextObject instanceof FieldCollectionData =>
                $contextObject->get($fieldName, $this->language),
            $contextObject instanceof Classificationstore => $this->getDataFromClassificationStore($contextObject),
            $contextObject instanceof BlockData => $this->getDataFromBlock($fieldName, $contextObject->getBlockData()),
            default => null,
        };
    }

    /**
     * Whether this context represents a container field (object brick, field collection item or
     * classification store) that could not be resolved on the current element - e.g. an ancestor
     * that does not have the object brick added. Callers walking ancestors for inheritance must not
     * mistake this for "no container at all", since that would read an unrelated, same-named field
     * directly on the element instead of continuing the ancestor walk.
     */
    public function isContainerContextUnresolved(): bool
    {
        return $this->containerFieldName !== null && $this->contextObject === null;
    }

    /**
     * @throws NotFoundException
     */
    public function getContextObjectFromElement(
        Concrete $object
    ): self {
        $fieldName = $this->containerFieldName ?? $this->resolveContainerFieldName();
        if ($fieldName === null) {
            return $this;
        }

        try {
            $elementContext = $object->get($fieldName);
        } catch (Exception) {
            throw new NotFoundException('field', $fieldName, 'name');
        }

        $brickType = $this->containerBrickType ?? $this->resolveContainerBrickType();
        if ($elementContext instanceof Objectbrick) {
            $elementContext = $brickType !== null ? $elementContext->get($brickType) : null;
        }

        return $this->createFieldContextData($elementContext, $fieldName, $brickType);
    }

    /**
     * The field name identifying the container on the class definition (object brick container,
     * field collection or classification store), used to re-locate the same container on an ancestor
     * even after it resolved to null on a closer element.
     */
    private function resolveContainerFieldName(): ?string
    {
        $contextObject = $this->getContextObject();

        return match (true) {
            $contextObject instanceof AbstractData,
            $contextObject instanceof FieldCollectionData,
            $contextObject instanceof Classificationstore => $contextObject->getFieldname(),
            default => null,
        };
    }

    /**
     * The specific object brick type, only relevant when the container is an object brick.
     */
    private function resolveContainerBrickType(): ?string
    {
        $contextObject = $this->getContextObject();

        return $contextObject instanceof AbstractData ? $contextObject->getType() : null;
    }

    private function createFieldContextData(
        FieldCollectionData|array|AbstractData|Classificationstore|null $contextObject,
        ?string $containerFieldName,
        ?string $containerBrickType
    ): self {
        return new self(
            $contextObject,
            $this->language,
            $this->classificationStoreGroupId,
            $this->classificationStoreKeyId,
            resolveInheritedValue: $this->resolveInheritedValue,
            containerFieldName: $containerFieldName,
            containerBrickType: $containerBrickType
        );
    }

    private function getDataFromBlock(string $fieldName, array $blockData): mixed
    {
        foreach ($blockData as $value) {
            if ($value instanceof BlockElement && $value->getName() === $fieldName) {
                return $value->getData();
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function getDataFromClassificationStore(Classificationstore $classificationstore): mixed
    {
        if ($this->getClassificationStoreKeyId() === null || $this->getClassificationStoreGroupId() === null) {
            return null;
        }

        return $classificationstore->getLocalizedKeyValue(
            $this->classificationStoreGroupId,
            $this->classificationStoreKeyId,
            $this->language
        );
    }
}
