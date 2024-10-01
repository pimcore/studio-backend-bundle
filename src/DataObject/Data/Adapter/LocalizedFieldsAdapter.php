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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
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

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class LocalizedFieldsAdapter implements SetterDataInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private SecurityServiceInterface $securityService,
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

                $adapter = $this->dataAdapterService->getDataAdapter($childFieldDefinition->getFieldType());
                $localizedField->setLocalizedValue(
                    $name,
                    $adapter->getDataForSetter(
                        $element,
                        $childFieldDefinition,
                        $name,
                        [$name => $fieldData],
                        new FieldContextData(language: $language)
                    ),
                    $language
                );
            }
        }

        return $localizedField;
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

        if ($contextData->getObjectbrick() !== null) {
            return $contextData->getObjectbrick()->get('localizedfields');
        }

        throw new InvalidArgumentException('Invalid context provided.');
    }
}
