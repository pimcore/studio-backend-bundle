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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\PatchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::ADAPTER_TAG)]
final readonly class IndexAdapter implements PatchAdapterInterface
{
    private const INDEX_KEY = 'index';

    /**
     * @throws ElementSavingFailedException
     */
    public function patch(ElementInterface $element, array $data): void
    {
        if (!$element instanceof AbstractObject || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $value = $data[$this->getIndexKey()];
        if (!is_int($value)) {
            throw new ElementSavingFailedException(
                null,
                sprintf('Invalid value provided for index "%s"', $value)
            );
        }

        $element->setIndex($value);
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
