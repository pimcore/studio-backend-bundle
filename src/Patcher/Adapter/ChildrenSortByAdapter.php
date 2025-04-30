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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::ADAPTER_TAG)]
final readonly class ChildrenSortByAdapter implements PatchAdapterInterface
{
    private const INDEX_KEY = 'childrenSortBy';

    /**
     * @throws ElementSavingFailedException|ForbiddenException
     */
    public function patch(ElementInterface $element, array $data, UserInterface $user): void
    {
        if (!$element instanceof AbstractObject || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        if (!$user->isAllowed(UserPermissions::OBJECTS_SORT_METHOD->value)) {
            throw new ForbiddenException('You are not allowed to change the sort method');
        }

        $value = $data[$this->getIndexKey()];
        if (!in_array(
            $value,
            [AbstractObject::OBJECT_CHILDREN_SORT_BY_DEFAULT, AbstractObject::OBJECT_CHILDREN_SORT_BY_INDEX],
            true
        )) {
            throw new ElementSavingFailedException(null, sprintf('Invalid sort method "%s"', $value));
        }

        $element->setChildrenSortBy($data[$this->getIndexKey()]);
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
