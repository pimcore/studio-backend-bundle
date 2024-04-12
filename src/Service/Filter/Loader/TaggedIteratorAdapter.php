<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service\Filter\Loader;

use Pimcore\Bundle\BackendPowerToolsBundle\PreconditionFilter\PreconditionFilterLoaderInterface;
use Pimcore\Bundle\BackendPowerToolsBundle\Provider\PreconditionFilterProvider;
use Pimcore\Bundle\StudioApiBundle\Service\Filter\FilterLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterLoaderInterface
{
    public function __construct(
        #[TaggedIterator('pimcore.studio_api.collection.filter')]
        private readonly iterable $taggedServices
    ) {
    }

    public function loadFilters(): array
    {
        return [... $this->taggedServices];
    }
}
