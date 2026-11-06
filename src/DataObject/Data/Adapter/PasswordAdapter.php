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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\BlockData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldCollectionData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Throwable;
use function array_key_exists;
use function is_string;
use function sprintf;

/**
 * Password fields never leave the API: reading returns a fixed placeholder instead of the stored value.
 * Writing a placeholder back is treated as "unchanged", every other value is a new password.
 *
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class PasswordAdapter implements SetterDataInterface, DataNormalizerInterface, DataExportInterface
{
    use ValidateObjectDataTrait;

    /**
     * Same placeholder as the core Password field type uses for its other read paths.
     */
    private const string MASK = '******';

    /**
     * The placeholder the Studio UI renders for password fields; echoing it back also means "unchanged".
     */
    private const string UI_PLACEHOLDER = '********';

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): mixed {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];
        if ($value === self::MASK || $value === self::UI_PLACEHOLDER) {
            // unchanged: hand back what is stored, never the placeholder and never an empty value
            return $this->getStoredValue($element, $key, $contextData);
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?string {
        return $this->mask($value);
    }

    public function getExportData(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): string {
        // same lookup as the generic export, so the export callers' field and context conventions apply
        $exportObject = $contextData?->getContextObject() ?: $object;
        $value = $fieldDefinition->getForCsvExport($exportObject, $contextData?->getLegacyParameters() ?? []);

        return $this->mask($value) ?? '';
    }

    private function mask(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? self::MASK : null;
    }

    /**
     * @throws ValidationException when the stored value cannot be determined: the save is rejected instead of
     * clearing the stored value or persisting the placeholder
     */
    private function getStoredValue(Concrete $element, string $key, ?FieldContextData $contextData): ?string
    {
        $language = $contextData?->getLanguage();
        $contextObject = $contextData?->getContextObject();

        try {
            $value = match (true) {
                // the context item is rebuilt without values, the stored one has the same type and index
                $contextObject instanceof FieldCollectionData =>
                    $this->getStoredCollectionItem($element, $contextObject)?->get($key, $language),
                $contextObject instanceof BlockData => $this->getStoredBlockValue($contextObject, $key, $language),
                $contextObject instanceof Localizedfield =>
                    $this->getStoredLocalizedValue($contextObject, $key, $language),
                $contextObject instanceof Concrete => $contextObject->get($key, $language),
                $contextObject !== null => $contextData?->getFieldValueFromContextObject($key),
                default => $element->get($key, $language),
            };
        } catch (Throwable $exception) {
            throw $this->unresolvable($key, $exception);
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function getStoredLocalizedValue(Localizedfield $localizedField, string $key, ?string $language): mixed
    {
        if ($language === null) {
            throw new ValidationException('unresolvable');
        }

        return $localizedField->getItems()[$language][$key] ?? null;
    }

    private function unresolvable(string $key, Throwable $previous): ValidationException
    {
        return new ValidationException(
            sprintf(
                'The unchanged value of password field "%s" cannot be kept in this position, set a new value instead',
                $key
            ),
            0,
            $previous
        );
    }

    private function getStoredBlockValue(BlockData $blockData, string $key, ?string $language): mixed
    {
        if (($blockData->getContextData()['inRebuiltContainer'] ?? false) === true) {
            throw new ValidationException('unresolvable');
        }

        foreach ($blockData->getBlockData() as $blockElement) {
            if (!$blockElement instanceof BlockElement) {
                continue;
            }

            if ($blockElement->getName() === $key) {
                return $blockElement->getData();
            }

            // a localized field inside the block keeps its values per language
            $elementData = $blockElement->getData();
            if ($language !== null && $elementData instanceof Localizedfield) {
                $localized = $elementData->getItems()[$language][$key] ?? null;
                if ($localized !== null) {
                    return $localized;
                }
            }
        }

        return null;
    }

    private function getStoredCollectionItem(Concrete $element, FieldCollectionData $rebuilt): ?FieldCollectionData
    {
        $fieldName = $rebuilt->getFieldname();
        $collection = $fieldName !== null ? $element->get($fieldName) : null;
        if (!$collection instanceof Fieldcollection) {
            return null;
        }

        $stored = $collection->getItems()[$rebuilt->getIndex()] ?? null;

        return $stored?->getType() === $rebuilt->getType() ? $stored : null;
    }
}
