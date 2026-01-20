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

namespace Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\ResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\ResolverLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final class DotNotationParser implements DotNotationParserInterface
{
    public function __construct(
        private readonly ResolverLoaderInterface $resolverLoader,
        private readonly ClassDefinitionRepositoryInterface $classDefinitionRepository
    ) {
    }

    /**
     * @var array<string, ResolverInterface>
     */
    private array $resolvers = [];

    /**
     * @var array<string, Data>
     */
    private array $fieldDefinitions = [];

    /**
     * @throws ParseException
     * @throws Exception
     */
    public function parse(Concrete $concreteObject, string $dotNotation): FieldDefinitionWrapper
    {
        $this->fieldDefinitions = $concreteObject->getClass()->getFieldDefinitions();

        return $this->doParse($dotNotation);
    }

    /**
     * @throws ParseException
     * @throws Exception
     */
    public function parseByClassId(string $classId, string $dotNotation): FieldDefinitionWrapper
    {
        $this->fieldDefinitions = $this->classDefinitionRepository->getClassDefinitionById($classId)->getFieldDefinitions();

        return $this->doParse($dotNotation);
    }

    private function doParse(string $dotNotation): FieldDefinitionWrapper
    {
        $parts = explode('.', $dotNotation);
        $resolvers = $this->getResolvers();

        $fd = null;
        foreach ($resolvers as $resolver) {
            if (!$resolver->canResolve($parts)) {
                continue;
            }
            $fd = $resolver->resolve($parts);

            break;

        }

        if (!$fd) {
            throw new ParseException('Could not parse dotNotation');
        }

        return $fd;
    }

    /**
     * @return array<string, ResolverInterface>
     */
    private function getResolvers(): array
    {
        if ($this->resolvers) {
            return $this->resolvers;
        }

        $this->resolvers = $this->resolverLoader->loadResolvers();

        foreach ($this->resolvers as $resolver) {
            $resolver->setResolvers($this->resolvers);
            $resolver->setFieldDefinitions($this->fieldDefinitions);
        }

        return $this->resolvers;
    }
}
