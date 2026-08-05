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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorTransformerLoader implements TransformerLoaderInterface
{
    public const string TRANSFORMER_TAG = 'pimcore.studio_backend.grid_transformer';

    /**
     * @param iterable<TransformerInterface> $taggedTransformers
     */
    public function __construct(
        #[AutowireIterator(self::TRANSFORMER_TAG)]
        private iterable $taggedTransformers,
    ) {
    }

    /**
     * @return array<string, TransformerInterface>
     */
    public function loadTransformers(): array
    {
        $transformers = [];
        foreach ($this->taggedTransformers as $transformer) {
            $transformers[$transformer->getKey()] = $transformer;
        }

        return $transformers;
    }
}
