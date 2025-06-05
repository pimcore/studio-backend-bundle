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
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function is_int;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class IndexAdapter implements UpdateAdapterInterface
{
    private const string INDEX_KEY = 'index';

    /**
     * @throws ElementSavingFailedException
     */
    public function update(ElementInterface $element, array $data): void
    {
        if ((!$element instanceof AbstractObject && !$element instanceof Document) ||
            !array_key_exists($this->getIndexKey(), $data)
        ) {
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
            ElementTypes::TYPE_DOCUMENT,
        ];
    }
}
