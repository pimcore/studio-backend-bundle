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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatchDataKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatcherActions;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class MultiSelectAdapter implements SetterDataInterface
{
    use ValidateObjectDataTrait;

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?array {
        if (!is_array($data[$key])) {
            return null;
        }
        $newData = $data[$key];

        if (!$isPatch) {
            return $newData;
        }

        return $this->handlePatch($element, $key, $newData, $contextData);
    }

    private function handlePatch(
        Concrete $element,
        string $key,
        array $newData,
        ?FieldContextData $contextData = null
    ): array {
        $action = $newData[PatchDataKeys::ACTION->value];
        $fieldData = $newData[PatchDataKeys::DATA->value];
        if ($action === null || $action === PatcherActions::REPLACE->value) {
            return $fieldData;
        }

        $existingValues = $this->getValidFieldValue($element, $key, $contextData);
        if ($action === PatcherActions::REMOVE->value) {
            return $this->removeValues($existingValues, $fieldData);
        }

        return $this->addValues($existingValues, $fieldData);
    }

    private function addValues(?array $existingValues, array $data): array
    {
        return array_unique(array_merge($existingValues, $data));
    }

    private function removeValues(?array $existingValues, array $data): array
    {
        return array_diff($existingValues, $data);
    }
}
