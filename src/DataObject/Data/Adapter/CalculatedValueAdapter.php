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

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DetailDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\CalculatedValueSnapshotServiceInterface;
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
use function mb_scrub;

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
        private CalculatedValueSnapshotServiceInterface $calculatedValueSnapshotService,
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

        if ($contextData?->isHistoricalVersion()) {
            // a version detail shows the value the version stores (pimcore.objects.versions.calculated_value_snapshot);
            // a version that stores none for the field (created before the option, auto-save, field of a
            // container) is shown the way the edit view shows it, as before
            $storedValueContext = new CalculatedValue($fieldDefinition->getName());
            $this->applyContextualData($storedValueContext, $fieldDefinition, $contextData, forStoredValue: true);

            if ($this->calculatedValueSnapshotService->hasStoredValue($object, $storedValueContext)) {
                return $this->toDetailValue(
                    $this->calculatedValueSnapshotService->getStoredValue($object, $storedValueContext)
                );
            }
        }

        $calculatedValue = new CalculatedValue($fieldDefinition->getName());
        $this->applyContextualData($calculatedValue, $fieldDefinition, $contextData);

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

        // the core stores whatever scalars a calculator returned, so anything the JSON response cannot carry
        // (invalid UTF-8, INF/NAN) is substituted instead of failing the whole version detail - the value is
        // historical and cannot be recomputed
        if (is_scalar($value) || $value instanceof Stringable) {
            return mb_scrub((string) $value, 'UTF-8');
        }

        // the edit view casts expression results to string as well; a structured value is shown as JSON
        $json = json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);

        return $json === false ? null : $json;
    }

    /**
     * @param bool $forStoredValue identify the field the way the generated getters of the class do, which is
     *                             how a version stores the value: object-level fields carry no owner name
     */
    private function applyContextualData(
        CalculatedValue $calculatedValue,
        CalculatedValueDefinition $fieldDefinition,
        ?FieldContextData $contextData,
        bool $forStoredValue = false,
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
            $forStoredValue ? null : $fieldDefinition->getName(),
            null,
            null,
            null,
            null,
            $fieldDefinition,
        );
    }
}
