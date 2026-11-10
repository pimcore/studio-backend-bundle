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
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class MultiValueAdapter implements SetterDataInterface, DataNormalizerInterface
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

        return null;
    }
}
