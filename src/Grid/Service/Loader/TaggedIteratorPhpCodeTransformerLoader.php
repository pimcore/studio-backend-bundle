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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\PhpCodeTransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\PhpCodeTransformerLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use function get_class;
use function sprintf;

/**
 * @internal
 */
final readonly class TaggedIteratorPhpCodeTransformerLoader implements PhpCodeTransformerLoaderInterface
{
    /**
     * @param iterable<PhpCodeTransformerInterface> $transformers
     */
    public function __construct(
        #[TaggedIterator(self::PHPCODE_TRANSFORMER_TAG)]
        private iterable $transformers,
    ) {
    }

    /**
     *  * @return array<string, PhpCodeTransformerInterface>
     */
    public function getTransformers(): array
    {
        $transformers = [];
        foreach ($this->transformers as $transformer) {
            $transformers[$transformer->getKey()] = $transformer;
        }

        return $transformers;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resolve(string $className): PhpCodeTransformerInterface
    {
        foreach ($this->transformers as $transformer) {
            if (get_class($transformer) === $className) {
                return $transformer;
            }
        }

        throw new InvalidArgumentException(
            sprintf('No PhpCode transformer found for class "%s"', $className)
        );
    }
}
