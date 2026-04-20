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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DetailDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class CalculatedValueAdapter implements SetterDataInterface, DetailDataInterface
{
    private const string OWNER_TYPE_OBJECT = 'object';

    private const string OWNER_TYPE_LOCALIZED_FIELD = 'localizedfield';

    private const string OWNER_TYPE_OBJECT_BRICK = 'objectbrick';

    private const string LOCALIZED_FIELDS_NAME = 'localizedfields';

    public function __construct(
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): null {
        return null;
    }

    public function getDetailData(
        Concrete $object,
        mixed $value,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null,
    ): ?string {
        if (!$fieldDefinition instanceof CalculatedValueDefinition) {
            return null;
        }

        $calculatedValue = new CalculatedValue($fieldDefinition->getName());
        $this->applyContextualData($calculatedValue, $fieldDefinition, $contextData);

        return $this->dataObjectServiceResolver->getCalculatedFieldValueForEditMode(
            $object,
            [],
            $calculatedValue,
        );
    }

    private function applyContextualData(
        CalculatedValue $calculatedValue,
        CalculatedValueDefinition $fieldDefinition,
        ?FieldContextData $contextData,
    ): void {
        $contextObject = $contextData?->getContextObject();

        if ($contextObject instanceof AbstractData) {
            $calculatedValue->setContextualData(
                self::OWNER_TYPE_OBJECT_BRICK,
                $contextObject->getFieldname(),
                $contextObject->getType(),
                $fieldDefinition->getName(),
                null,
                null,
                $fieldDefinition,
            );

            return;
        }

        $language = $contextData?->getLanguage();

        if ($language !== null) {
            $calculatedValue->setContextualData(
                self::OWNER_TYPE_LOCALIZED_FIELD,
                self::LOCALIZED_FIELDS_NAME,
                null,
                $language,
                null,
                null,
                $fieldDefinition,
            );

            return;
        }

        $calculatedValue->setContextualData(
            self::OWNER_TYPE_OBJECT,
            $fieldDefinition->getName(),
            null,
            null,
            null,
            null,
            $fieldDefinition,
        );
    }
}
