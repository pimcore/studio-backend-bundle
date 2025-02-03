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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class EncryptedFieldAdapter implements SetterDataInterface, DataNormalizerInterface
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
    ): ?EncryptedField {
        if (!$fieldDefinition instanceof EncryptedFieldDefinition) {
            return null;
        }

        $delegateFieldDefinition = $fieldDefinition->getDelegateDatatypeDefinition();
        if (!$delegateFieldDefinition instanceof Data) {
            return null;
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
        if (!$fieldDefinition instanceof EncryptedFieldDefinition) {
            return null;
        }

        if (!$value instanceof EncryptedField) {
            return $value;
        }

        $plainValue = $value->getPlain();
        $delegateFieldDefinition = $fieldDefinition->getDelegate();

        return $this->dataService->getNormalizedValue($plainValue, $delegateFieldDefinition)
            ?? $plainValue;
    }

    private function handleDelegatedField(
        Concrete $element,
        UserInterface $user,
        Data $delegate,
        string $key,
        array $data,
        bool $isPatch,
        ?FieldContextData $contextData = null
    ): ?EncryptedField {
        $adapter = $this->dataAdapterService->tryDataAdapter($delegate->getFieldType());
        if ($adapter instanceof SetterDataInterface) {
            return new EncryptedField(
                $delegate,
                $adapter->getDataForSetter($element, $delegate, $key, $data, $user, $contextData, $isPatch)
            );
        }

        return null;
    }
}
