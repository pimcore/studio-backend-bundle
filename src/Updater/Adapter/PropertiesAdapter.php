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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class PropertiesAdapter implements UpdateAdapterInterface
{
    public function __construct(private PropertyRepositoryInterface $propertyRepository)
    {
    }

    public function update(ElementInterface $element, array $data): void
    {
        if (!array_key_exists($this->getIndexKey(), $data)) {
            return;
        }
        $this->propertyRepository->updateElementProperties($element, $data);
    }

    public function getIndexKey(): string
    {
        return PropertyRepositoryInterface::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
