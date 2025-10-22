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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\PhpCodeTransformerCollectionException;
use function sprintf;

/**
 * @internal
 */
final class PhpCodeTransformerCollector implements PhpCodeTransformerCollectorInterface
{
    public function __construct(
        private readonly PhpCodeTransformerLoaderInterface $loader,
    ) {}

    /**
     * @throws PhpCodeTransformerCollectionException
     */
    public function collect(): array
    {
        $transformers = [];

        foreach ($this->loader->getTransformers() as $transformer) {
            if (!method_exists($transformer, 'getKey') || !method_exists($transformer, 'getName')) {
                throw new PhpCodeTransformerCollectionException(
                    sprintf(
                        'Invalid transformer: class "%s" must implement getKey() and getName() methods.',
                        get_class($transformer)
                    )
                );
            }

            $transformers[] = [
                'key'         => $transformer->getKey(),
                'label'       => $transformer->getName(),
                'description' => $transformer->getDescription(),
                'class'       => get_class($transformer),
            ];
        }

        return $transformers;
    }
}
