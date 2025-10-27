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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\PhpCodeTransformerEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\PhpCodeTransformer;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\PhpCodeTransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\PhpCodeTransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class PhpCodeTransformerService implements PhpCodeTransformerServiceInterface
{
    public function __construct(
        private PhpCodeTransformerLoaderInterface $loader,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getPhpCodeTransformers(): Collection
    {
        return $this->getTransformerCollection($this->loader->getTransformers());
    }

    /**
     * Converts an array of transformers into a Collection of PhpCodeTransformer DTOs
     *
     * @param PhpCodeTransformerInterface[] $transformers Array of PhpCodeTransformerInterface instances
     *
     * @return Collection<PhpCodeTransformer>
     */
    private function getTransformerCollection(array $transformers): Collection
    {
        $items = [];

        foreach ($transformers as $transformer) {
            $item = new PhpCodeTransformer(
                key: $transformer->getKey(),
                label: $transformer->getName(),
            );

            $this->eventDispatcher->dispatch(
                new PhpCodeTransformerEvent($item),
                PhpCodeTransformerEvent::EVENT_NAME
            );

            $items[] = $item;
        }

        return new Collection(count($items), $items);
    }
}
