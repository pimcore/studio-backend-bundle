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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\PhpCodeTransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\PhpCodeTransformerLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorPhpCodeTransformerLoader implements PhpCodeTransformerLoaderInterface
{
    public function __construct(
        #[AutowireIterator(self::PHPCODE_TRANSFORMER_TAG)]
        private iterable $transformers,
    ) {
    }

    public function getTransformers(): array
    {
        $transformers = [];
        foreach ($this->transformers as $transformer) {
            $transformers[$transformer->getKey()] = $transformer;
        }

        return $transformers;
    }

    public function resolve(string $key): PhpCodeTransformerInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->getKey() === $key) {
                return $transformer;
            }
        }

        throw new NotFoundException('PhpCode transformer', $key, 'key');
    }
}
