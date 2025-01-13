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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateFieldTypeTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class LocalizedFieldsAdapter implements
    SetterDataInterface, DataNormalizerInterface, DataInheritanceInterface
{
    use ValidateFieldTypeTrait;

    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService,
        private InheritanceServiceInterface $inheritanceService,
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver
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
        ?FieldContextData $contextData = null
    ): ?Localizedfield {
        if (!$fieldDefinition instanceof Localizedfields) {
            return null;
        }

        $languageData = $this->getAllowedLanguages($element, $data[$key]);
        $localizedField = $this->getLocalizedField($contextData);
        $localizedField->setObject($element);

        foreach ($languageData as $language => $fields) {
            foreach ($fields as $name => $fieldData) {
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
                    new FieldContextData(language: $language)
                );
                if (!$this->validateEncryptedField($childFieldDefinition, $value)) {
                    continue;
                }

                $localizedField->setLocalizedValue($name, $value, $language);
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
        if ($originalValue === null) {
            return null;
        }
        $languages = array_keys($originalValue);
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
    ): array
    {
        if (!$fieldDefinition instanceof Localizedfields) {
            return [];
        }

        $inheritedData = [];
        $contextObject = $contextData?->getContextObject();
        $fields = $fieldDefinition->getChildren();

        foreach ($fields as $field) {
            foreach ($this->toolResolver->getValidLanguages() as $language) {
                $inheritedData[$field->getName()][$language] = $this->inheritanceService->processFieldDefinition(
                    $object,
                    $field,
                    $key,
                    new FieldContextData(contextObject: $contextObject, language: $language)
                );
            }
        }

        return $inheritedData;
    }

    private function getAllowedLanguages(
        Concrete $element,
        array $languageData
    ): array {
        $user = $this->securityService->getCurrentUser();
        if ($user->isAdmin()) {
            return $languageData;
        }

        /** @var User $user */
        $allowedLanguages = Service::getLanguagePermissions(
            $element,
            $user,
            ElementPermissions::LANGUAGE_EDIT_PERMISSIONS
        );

        if (empty($allowedLanguages)) {
            return [];
        }

        foreach ($languageData as $language => $data) {
            if (!in_array($language, $allowedLanguages, true)) {
                unset($languageData[$language]);
            }
        }

        return $languageData;
    }

    private function getLocalizedField(?FieldContextData $contextData): Localizedfield
    {
        if ($contextData === null) {
            return new Localizedfield();
        }

        if ($contextData->getContextObject() !== null) {
            return $contextData->getFieldValueFromContextObject('localizedfields');
        }

        throw new InvalidArgumentException('Invalid context provided.');
    }
}
