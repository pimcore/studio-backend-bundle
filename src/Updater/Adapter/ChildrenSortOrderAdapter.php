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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class ChildrenSortOrderAdapter implements UpdateAdapterInterface
{
    private const INDEX_KEY = 'childrenSortOrder';

    /**
     * @throws ElementSavingFailedException
     */
    public function update(ElementInterface $element, array $data): void
    {
        if (!$element instanceof AbstractObject || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $value = $data[$this->getIndexKey()];
        if (!in_array(
            $value,
            ['ASC', 'DESC'],
            true
        )) {
            throw new ElementSavingFailedException(null, sprintf('Invalid sort order "%s"', $value));
        }

        $element->setChildrenSortOrder($value);
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
