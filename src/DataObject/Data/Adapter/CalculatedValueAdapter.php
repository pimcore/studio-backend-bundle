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

use const JSON_THROW_ON_ERROR;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DetailDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\UserInterface;
use Stringable;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_scalar;
use function json_encode;

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

        if ($contextData?->isHistoricalVersion()) {
            // a version detail shows the value the version stores (Pimcore 2026.4+ with
            // pimcore.objects.versions.calculated_value_snapshot enabled); the core computes the value live
            // when the version holds none, same as it does for the edit view
            return $this->toDetailValue(
                $this->dataObjectServiceResolver->getCalculatedFieldValue($object, $calculatedValue)
            );
        }

        return $this->dataObjectServiceResolver->getCalculatedFieldValueForEditMode(
            $object,
            [],
            $calculatedValue,
        );
    }

    private function toDetailValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        // the edit view casts expression results to string as well; a structured value is shown as JSON
        return json_encode($value, JSON_THROW_ON_ERROR);
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
