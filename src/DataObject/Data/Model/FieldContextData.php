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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldCollectionData;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use function is_array;

/**
 * @internal
 */
final readonly class FieldContextData
{
    public function __construct(
        private AbstractData|array|FieldCollectionData|Classificationstore|null $contextObject = null,
        private ?string $language = null,
        private ?int $classificationStoreGroupId = null,
        private ?int $classificationStoreKeyId = null,
    ) {
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getContextObject(): FieldCollectionData|array|AbstractData|Classificationstore|null
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
     * @throws Exception
     */
    public function getFieldValueFromContextObject(string $fieldName): mixed
    {
        $contextObject = $this->getContextObject();

        return match (true) {
            $contextObject instanceof AbstractData, $contextObject instanceof FieldCollectionData =>
                $contextObject->get($fieldName, $this->language),
            $contextObject instanceof Classificationstore => $this->getDataFromClassificationStore($contextObject),
            is_array($contextObject) => $this->getDataFromBlock($fieldName, $contextObject),
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
            $fieldValue = $value[$fieldName] ?? null;
            if ($fieldValue instanceof BlockElement) {
                return $fieldValue->getData();
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
