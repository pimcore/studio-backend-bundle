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

use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::ADAPTER_TAG)]
final readonly class LockAdapter implements PatchAdapterInterface
{
    private const INDEX_KEY = 'locked';

    private const UNLOCK_PROPAGATE = 'unlockPropagate';

    public function patch(ElementInterface $element, array $data, UserInterface $user): void
    {
        if (!array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $isUnlockPropagate = $data[$this->getIndexKey()] === self::UNLOCK_PROPAGATE;
        if ($element instanceof AbstractElement && $isUnlockPropagate) {
            $element->unlockPropagate();
        }

        $element->setLocked($isUnlockPropagate ? null : $data[$this->getIndexKey()]);
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
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
