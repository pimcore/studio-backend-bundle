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

namespace Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\ResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\ResolverLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorResolverLoader implements ResolverLoaderInterface
{
    public const string FIELD_DEFINITION_RESOLVER_TAG = 'pimcore.studio_backend.field_definition_resolver';

    /**
     * @param iterable<ResolverInterface> $taggedFieldDefinitionResolvers
     */
    public function __construct(
        #[AutowireIterator(self::FIELD_DEFINITION_RESOLVER_TAG)]
        private iterable $taggedFieldDefinitionResolvers,
    ) {
    }

    /**
     * @return array<string, ResolverInterface>
     */
    public function loadResolvers(): array
    {
        $fieldDefinitionResolvers = [];
        foreach ($this->taggedFieldDefinitionResolvers as $fieldDefinitionResolver) {
            $fieldDefinitionResolvers[$fieldDefinitionResolver->getResolverName()] = $fieldDefinitionResolver;
        }

        return $fieldDefinitionResolvers;
    }
}
