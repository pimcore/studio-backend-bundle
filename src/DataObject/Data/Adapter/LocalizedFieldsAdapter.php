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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class LocalizedFieldsAdapter implements
    SetterDataInterface,
    DataNormalizerInterface,
    DataInheritanceInterface,
    SearchPreviewDataInterface
{
    use ValidateObjectDataTrait;

    public const string LOCALIZED_FIELDS_KEY = 'localizedfields';

    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService,
        private InheritanceServiceInterface $inheritanceService,
        private LanguageServiceInterface $languageService,
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?Localizedfield {
        if (!$fieldDefinition instanceof Localizedfields) {
            return null;
        }

        $languageData = $this->getAllowedLanguages($element, $user, $data[$key]);
        $localizedField = $this->getLocalizedField($contextData, $element);
        $localizedField->setObject($element);

        foreach ($languageData as $name => $localizedData) {
            foreach ($localizedData as $language => $fieldData) {
                $childFieldDefinition = $fieldDefinition->getFieldDefinition($name);
                if ($childFieldDefinition === null) {
                    continue;
                }

                $adapter = $this->dataAdapterService->tryDataAdapter($childFieldDefinition->getFieldType());
                if (!$adapter) {
                    continue;
                }

                $value = $adapter->getDataForSetter(
                    $element,
                    $childFieldDefinition,
                    $name,
                    [$name => $fieldData],
                    $user,
                    new FieldContextData(language: $language),
                    $isPatch
                );
                if (!$this->validateEncryptedField($childFieldDefinition, $value)) {
                    continue;
                }
                $localizedField->setLocalizedValue($name, $value, $language);
                $localizedField->markLanguageAsDirty($language);
            }
        }

        return $localizedField;
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!$value instanceof Localizedfield || !$fieldDefinition instanceof Localizedfields) {
            return null;
        }

        $value->loadLazyData();
        $originalValue = $fieldDefinition->normalize($value);
        if (empty($originalValue)) {
            return null;
        }
        $languages = $this->languageService->getUserAllowedLanguages(
            $value->getObject(),
            $this->securityService->getCurrentUser(),
            ElementPermissions::LANGUAGE_VIEW_PERMISSIONS
        );

        $attributes = array_keys(reset($originalValue));
        $result = [];
        foreach ($attributes as $attribute) {
            foreach ($languages as $language) {
                try {
                    $localizedValue = $value->getLocalizedValue($attribute, $language);
                } catch (Exception $exception) {
                    throw new DatabaseException(
                        sprintf(
                            'Error while normalizing localized field: %s',
                            $exception->getMessage()
                        )
                    );
                }
                $fieldDefinition = $value->getFieldDefinition($attribute, $value->getContext());
                if ($fieldDefinition === null) {
                    throw new NotFoundException(type: 'Field Definition', id: $attribute);
                }

                $localizedValue = $this->dataService->getNormalizedValue($localizedValue, $fieldDefinition);
                $result[$attribute][$language] = $localizedValue;
            }
        }

        return $result;
    }

    public function getFieldInheritance(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): array {
        if (!$fieldDefinition instanceof Localizedfields) {
            return [];
        }

        $inheritedData = [];
        $contextObject = $contextData?->getContextObject();
        $fields = $this->processFieldChildren($fieldDefinition->getChildren());

        foreach ($fields as $field) {
            foreach ($this->toolResolver->getValidLanguages() as $language) {
                $fieldKey = $field->getName();
                $inheritedData[$fieldKey][$language] = $this->inheritanceService->processFieldDefinition(
                    $object,
                    $field,
                    $fieldKey,
                    new FieldContextData(contextObject: $contextObject, language: $language)
                );
            }
        }

        return $inheritedData;
    }

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if (!$value instanceof Localizedfield || !$fieldDefinition instanceof Localizedfields) {
            return $data;
        }

        $originalValue = $fieldDefinition->normalize($value);
        if ($originalValue === null) {
            return $data;
        }
        $language = $this->getPreviewLanguage($value);
        $attributes = array_keys(reset($originalValue));

        foreach ($attributes as $attribute) {
            try {
                $localizedValue = $value->getLocalizedValue($attribute, $language);
            } catch (Exception $exception) {
                throw new DatabaseException(
                    sprintf(
                        'Error while getting localized field preview data: %s',
                        $exception->getMessage()
                    )
                );
            }
            $fieldDefinition = $value->getFieldDefinition($attribute, $value->getContext());
            if ($fieldDefinition === null) {
                throw new NotFoundException(type: 'Field Definition', id: $attribute);
            }

            $fieldName = $this->dataService->getPreviewFieldName($fieldDefinition);
            $localizedValue = $this->dataService->getPreviewFieldData($localizedValue, $fieldDefinition, $data);
            $key = sprintf('%s (%s)', $fieldName, $language);
            $data[$key] = $localizedValue[$fieldName];
        }

        return $data;
    }

    private function getAllowedLanguages(
        Concrete $element,
        UserInterface $user,
        array $languageData
    ): array {
        if ($user->isAdmin()) {
            return $languageData;
        }

        $userLanguages = $this->languageService->getUserAllowedLanguages(
            $element,
            $user,
            ElementPermissions::LANGUAGE_EDIT_PERMISSIONS
        );

        if (empty($userLanguages)) {
            return [];
        }

        foreach ($languageData as $attribute => $data) {
            foreach ($data as $language => $value) {
                if (!in_array($language, $userLanguages, true)) {
                    unset($languageData[$attribute][$language]);
                }
            }
        }

        return $languageData;
    }

    private function getPreviewLanguage(Localizedfield $value): ?string
    {
        $defaultLanguage = $this->toolResolver->getDefaultLanguage();
        $user = $this->securityService->getCurrentUser();
        if ($user->isAdmin()) {
            return $defaultLanguage;
        }

        $userLanguages = $this->languageService->getUserAllowedLanguages(
            $value->getObject(),
            $user,
            ElementPermissions::LANGUAGE_VIEW_PERMISSIONS
        );

        if (empty($userLanguages)) {
            return null;
        }

        return in_array($defaultLanguage, $userLanguages, true) ? $defaultLanguage : reset($userLanguages);
    }

    /**
     * @throws Exception
     */
    private function getLocalizedField(?FieldContextData $contextData, Concrete $element): Localizedfield
    {
        if ($contextData === null) {
            $localizedField =  $this->getValidFieldValue($element, self::LOCALIZED_FIELDS_KEY);
            if (!$localizedField instanceof Localizedfield) {
                return new Localizedfield();
            }

            return $localizedField;
        }

        if ($contextData->getContextObject() !== null) {
            return $contextData->getFieldValueFromContextObject(self::LOCALIZED_FIELDS_KEY);
        }

        throw new InvalidArgumentException('Invalid context provided.');
    }

    private function processFieldChildren(array $children): array
    {
        $fields = [];

        foreach ($children as $child) {
            if (!$child instanceof Layout) {
                $fields[] = $child;

                continue;
            }

            foreach ($this->processFieldChildren($child->getChildren()) as $nestedChild) {
                $fields[] = $nestedChild;
            }
        }

        return $fields;
    }
}
