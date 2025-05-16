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

namespace Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver;

use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\LocalizedFieldServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use function count;
use function sprintf;

/**
 * @internal
 */
final class BlockResolver extends AbstractResolver
{
    public function __construct(
        private readonly LocalizedFieldServiceInterface $localizedFieldService
    ) {
    }

    public function getResolverName(): string
    {
        return 'block';
    }

    public function canResolve(array $dotNotationParts): bool
    {
        if (!count($dotNotationParts) >= 2) {
            return false;
        }

        try {
            $fd = $this->getFieldDefinition($dotNotationParts[0]);
        } catch (ParseException) {
            return false;
        }

        if ($fd instanceof Block) {
            return true;
        }

        return false;
    }

    public function resolve(array $dotNotationParts): FieldDefinitionWrapper
    {
        $fd = $this->getFieldDefinition($dotNotationParts[0]);

        if (!$fd instanceof Block) {
            throw new ParseException('Class Definition has to be of type Block');
        }

        $item = array_filter($fd->getChildren(), function (Data $field) use ($dotNotationParts) {
            return $field->getName() === $dotNotationParts[1];
        });
        $item = reset($item);

        if (!$item) {
            throw new ParseException(sprintf('Block field definition "%s" does not exist', $dotNotationParts[1]));
        }

        $localized = false;
        if ($item instanceof Data\Localizedfields && count($dotNotationParts) >= 3) {
            $localized = true;
            $item = $this->localizedFieldService->getFieldDefinition($item, $dotNotationParts[2]);
        }

        return $this->wrapFieldDefinition(
            fieldDefinition: $item,
            containerType: 'block',
            fieldname: $dotNotationParts[1],
            subContainerType: $localized ? 'localizedfield' : null,
            subContainerKey: $localized ? $dotNotationParts[2] : null,
        );
    }
}
