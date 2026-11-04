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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use function array_key_exists;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function sprintf;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var array<string, Data>
     */
    private array $fieldDefinitions = [];

    /**
     * @param array<string, ResolverInterface> $resolvers
     */
    private array $resolvers = [];

    abstract public function getResolverName(): string;

    abstract public function canResolve(array $dotNotationParts): bool;

    abstract public function resolve(array $dotNotationParts): FieldDefinitionWrapper;

    /**
     * @param array<string, Data> $fieldDefinitions
     */
    public function setFieldDefinitions(array $fieldDefinitions): void
    {
        $this->fieldDefinitions = $fieldDefinitions;
    }

    /**
     * @return array<string, Data>
     */
    protected function getFieldDefinitions(): array
    {
        return $this->fieldDefinitions;
    }

    /**
     * @throws ParseException
     */
    protected function getFieldDefinition(string $key): Data
    {
        if (!array_key_exists($key, $this->getFieldDefinitions())) {
            throw new ParseException(sprintf('Field definition "%s" does not exist', $key));
        }

        return $this->getFieldDefinitions()[$key];
    }

    /**
     * @param array<string, ResolverInterface> $resolvers
     */
    public function setResolvers(array $resolvers): void
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @return array<string, ResolverInterface>
     */
    protected function getResolvers(): array
    {
        return $this->resolvers;
    }

    /**
     * @throws ParseException
     */
    protected function checkForSubBlockContainer(Data $fd, array $dotNotationParts, Definition $definition): Data
    {
        if (!$fd instanceof Block) {
            return $fd;
        }

        // Skip the container field and the brick/collection key, then start at the block itself:
        // the block can sit directly in the container or inside its localized fields
        // (e.g. content.UspArea.localizedfields.buttons.buttonColor)
        $blockIndex = array_search($fd->getName(), array_slice($dotNotationParts, 2), true);
        if ($blockIndex === false) {
            return $fd;
        }
        $dotNotationParts = array_values(array_slice($dotNotationParts, $blockIndex + 2));

        if (count($dotNotationParts) < 2) {
            return $fd;
        }

        // The block is looked up by name; inside localized fields it is not one of the
        // container's top-level field definitions, so hand the resolved block over directly
        $resolver = $this->getResolvers()['block'];
        $resolver->setFieldDefinitions([$fd->getName() => $fd]);

        if (!$resolver->canResolve($dotNotationParts)) {
            return $fd;
        }

        return $resolver->resolve($dotNotationParts)->getFieldDefinition();
    }

    protected function wrapFieldDefinition(
        Data $fieldDefinition,
        string $containerType,
        string $fieldname,
        ?string $subContainerType = null,
        ?string $subContainerKey = null
    ): FieldDefinitionWrapper {
        return new FieldDefinitionWrapper(
            $fieldDefinition,
            $containerType,
            $fieldname,
            $subContainerType,
            $subContainerKey
        );
    }
}
