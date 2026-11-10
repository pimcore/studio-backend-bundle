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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataExportInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue as MultiValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * The adapter of a `multiValue` key, which is a wrapper over the type it delegates to - the
 * `encryptedField` of this bundle's adapters is its sibling, and core forwards the value itself to
 * the inner type from the same wrapper ({@see MultiValueDefinition::getDataFromEditmode()},
 * {@see MultiValueDefinition::normalize()}).
 *
 * One value of the inner type is what every method here is handed: the qualified write path takes a
 * `multiValue` key's slots through it one entry at a time
 * ({@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ClassificationStoreAdapter}),
 * and the list a slot may hold is core's, addressed through `setKeyValues()`/`getKeyValues()` rather
 * than through a field definition.
 *
 * A wrapper carrying no inner type hands the value back as it stands, which is what core stores and
 * reads for one ({@see MultiValueDefinition::normalize()}: "the loader builds a delegate-less
 * wrapper rather than refusing the definition blob"). Answering null instead would write null over
 * the value the request supplied, under a successful response.
 *
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class MultiValueAdapter implements SetterDataInterface, DataNormalizerInterface, DataExportInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService
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
    ): mixed {
        if (!$fieldDefinition instanceof MultiValueDefinition) {
            return null;
        }

        $delegateFieldDefinition = $fieldDefinition->getDelegate();
        if (!$delegateFieldDefinition instanceof Data) {
            // no inner type to convert the value, so it is stored as it stands - never as null,
            // which the write would put at the slot in place of what was supplied
            return $data[$key] ?? null;
        }

        return $this->handleDelegatedField(
            $element,
            $user,
            $delegateFieldDefinition,
            $key,
            $data,
            $isPatch,
            $contextData
        );
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): mixed {
        if (!$fieldDefinition instanceof MultiValueDefinition) {
            return null;
        }

        $delegateFieldDefinition = $fieldDefinition->getDelegate();
        if (!$delegateFieldDefinition instanceof Data) {
            return $value;
        }

        return $this->dataService->getNormalizedValue($value, $delegateFieldDefinition) ?? $value;
    }

    private function handleDelegatedField(
        Concrete $element,
        UserInterface $user,
        Data $delegate,
        string $key,
        array $data,
        bool $isPatch,
        ?FieldContextData $contextData = null
    ): mixed {
        $adapter = $this->dataAdapterService->tryDataAdapter($delegate->getFieldType());
        if ($adapter instanceof SetterDataInterface) {
            return $adapter->getDataForSetter($element, $delegate, $key, $data, $user, $contextData, $isPatch);
        }

        // the inner type converts nothing of its own, the way one with no adapter is stored by the
        // unqualified path: the supplied value is the value, and null here would destroy it
        return $data[$key] ?? null;
    }

    /**
     * The export of one value of the key, formatted by the INNER type - the cell the grid shows is
     * normalized by it ({@see self::normalize()}), and the two surfaces have to agree.
     *
     * Without this the export falls through to {@see MultiValueDefinition::getForCsvExport()}, which
     * the wrapper does not override, so the value would be formatted by
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data::getForCsvExport()} - the raw value cast
     * to a string - while the cell shows the inner type's own formatting. The sibling wrapper does
     * the same thing, in core ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField::getForCsvExport()})
     * and here ({@see EncryptedFieldAdapter::getExportData()}).
     *
     * The value itself is not read here: the caller hands the one value of the key it exports over
     * in the legacy parameters as `injectedData`, which core's own read of a field honours ahead of
     * the object ({@see \Pimcore\Model\DataObject\ClassDefinition\Data::getDataFromObjectParam()}),
     * and those parameters travel to the inner type untouched.
     */
    public function getExportData(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): string {
        if (!$fieldDefinition instanceof MultiValueDefinition) {
            return '';
        }

        $delegateFieldDefinition = $fieldDefinition->getDelegate();
        if (!$delegateFieldDefinition instanceof Data) {
            // no inner type to format the value, so the wrapper's own export stands
            return $fieldDefinition->getForCsvExport($object, $contextData?->getLegacyParameters() ?? []);
        }

        return $this->dataService->getExportFieldValue($object, $delegateFieldDefinition, $key, $contextData);
    }
}
