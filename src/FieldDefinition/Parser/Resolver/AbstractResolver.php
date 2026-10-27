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
        // Remove the first two parts of the dot notation since we have to check recursively vor sub containers
        unset($dotNotationParts[0]);
        unset($dotNotationParts[1]);
        $dotNotationParts = array_values($dotNotationParts);

        if (!count($dotNotationParts) >= 2) {
            return $fd;
        }

        if ($fd instanceof Block) {
            $resolver = $this->getResolvers()['block'];
            $resolver->setFieldDefinitions(
                $definition->getFieldDefinitions()
            );

            if (!$resolver->canResolve($dotNotationParts)) {
                return $fd;
            }

            $fd = $resolver->resolve($dotNotationParts)->getFieldDefinition();
        }

        return $fd;

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
