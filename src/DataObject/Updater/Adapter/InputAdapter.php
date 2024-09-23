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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Updater\Adapter;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.editable.update_adapter')]
final readonly class InputAdapter implements EditableUpdateAdapterInterface
{
    public function update(Concrete $element, Data $fieldDefinition, string $key, array $data): bool
    {
        if (!array_key_exists($key, $data) || !($this->supports($fieldDefinition))) {
            return false;
        }

        /** @var ResourcePersistenceAwareInterface $fieldDefinition */
        $dataFromResource = $fieldDefinition->getDataFromResource($data[$key], $element);
        $element->setValue($key, $dataFromResource);

        return true;
    }

    private function supports(Data $fieldDefinition): bool
    {
        return $fieldDefinition instanceof Numeric || $fieldDefinition instanceof Input;
    }
}
