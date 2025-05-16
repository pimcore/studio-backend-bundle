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

interface ResolverInterface
{

    public function getResolverName(): string;

    public function canResolve(array $dotNotationParts): bool;

    /**
     * @throws ParseException
     */
    public function resolve(array $dotNotationParts): FieldDefinitionWrapper;

    /**
     * @param array<string, Data> $fieldDefinitions
     */
    public function setFieldDefinitions(array $fieldDefinitions): void;

    /**
     * @param array<string, ResolverInterface> $resolvers
     */
    public function setResolvers(array $resolvers): void;

}