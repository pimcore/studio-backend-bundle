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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class EncryptedFieldAdapter implements SetterDataInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?EncryptedField {
        if (!$fieldDefinition instanceof EncryptedFieldDefinition) {
            return null;
        }

        $delegateFieldDefinition = $fieldDefinition->getDelegateDatatypeDefinition();
        if (!$delegateFieldDefinition) {
            return null;
        }

        return $this->handleDelegatedField(
            $element,
            $delegateFieldDefinition,
            $fieldDefinition,
            $key,
            $data,
            $contextData
        );
    }

    private function handleDelegatedField(
        Concrete $element,
        Data $delegateFieldDefinition,
        EncryptedFieldDefinition $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?EncryptedField {
        $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof SetterDataInterface) {
            return new EncryptedField(
                $fieldDefinition->getDelegate(),
                $adapter->getDataForSetter($element, $delegateFieldDefinition, $key, $data, $contextData)
            );
        }

        return null;
    }
}
