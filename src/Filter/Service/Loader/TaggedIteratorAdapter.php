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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterServiceLoaderInterface
{
    public const FILTER_SERVICE_TAG = 'pimcore.studio_backend.filter_service';

    public function __construct(
        #[AutowireIterator(self::FILTER_SERVICE_TAG)]
        private readonly iterable $taggedFilterServices,
    ) {
    }

    public function loadFilterServices(): array
    {
        return [...$this->taggedFilterServices];
    }
}
