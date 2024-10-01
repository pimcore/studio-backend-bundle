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
    ){}

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?EncryptedField
    {
        if(!($fieldDefinition instanceof Data\EncryptedField)) {
            return null;
        }

        $delegateFieldDefinition = $fieldDefinition->getDelegateDatatypeDefinition();
        if(!$delegateFieldDefinition) {
            return null;
        }
        $adapter = $this->dataAdapterService->getDataAdapter($delegateFieldDefinition->getFieldType());
        $result = $adapter->getDataForSetter($element, $delegateFieldDefinition, $key, $data);

        return new EncryptedField($fieldDefinition->getDelegate(), $result);
    }
}
