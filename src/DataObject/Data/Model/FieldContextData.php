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
        private array $legacyParameters = []
    ) {
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getContextObject():
    FieldCollectionData|BlockData|AbstractData|Classificationstore|Localizedfield|Concrete|null
    {
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
     * @throws NotFoundException
     */
    public function getContextObjectFromElement(
        Concrete $object
    ): self {
        $contextObject = $this->getContextObject();
        if (!$contextObject instanceof AbstractData &&
            !$contextObject instanceof FieldCollectionData &&
            !$contextObject instanceof Classificationstore
        ) {
            return $this;
        }

        try {
            $elementContext = $object->get($contextObject->getFieldname());
        } catch (Exception) {
            throw new NotFoundException('field', $contextObject->getFieldname(), 'name');
        }

        if ($elementContext instanceof Objectbrick) {
            $elementContext = $elementContext->get($contextObject->getType());
        }

        return $this->createFieldContextData($elementContext);
    }

    private function createFieldContextData(
        FieldCollectionData|array|AbstractData|Classificationstore|null $contextObject
    ): self {
        return new self(
            $contextObject,
            $this->language,
            $this->classificationStoreGroupId,
            $this->classificationStoreKeyId
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
