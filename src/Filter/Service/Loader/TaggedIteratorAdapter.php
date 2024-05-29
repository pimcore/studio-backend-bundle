<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterServiceLoaderInterface
{
    public const FILTER_SERVICE_TAG = 'pimcore.studio_backend.filter_service';

    public function __construct(
        #[TaggedIterator(self::FILTER_SERVICE_TAG)]
        private readonly iterable $taggedFilterServices,
    ) {
    }

    public function loadFilterServices(): array
    {
        return [...$this->taggedFilterServices];
    }
}
