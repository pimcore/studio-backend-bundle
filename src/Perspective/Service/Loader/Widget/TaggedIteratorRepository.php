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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\WidgetConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorRepository implements ConfigRepositoryLoaderInterface
{
    use MustImplementInterfaceTrait;

    public const string REPOSITORY_TAG = 'pimcore.studio_backend.widget_repository';

    public function __construct(
        #[TaggedIterator(self::REPOSITORY_TAG)]
        private readonly iterable $taggedRepositories,
    ) {
    }

    /**
     * @throws MustImplementInterfaceException
     *
     * @return WidgetConfigRepositoryInterface[]
     */
    public function loadRepositories(): array
    {
        $repositories = [];
        foreach ($this->taggedRepositories as $repository) {
            $this->checkInterface($repository::class, WidgetConfigRepositoryInterface::class);
            $repositories[] = $repository;
        }

        return $repositories;
    }

    /**
     * @throws MustImplementInterfaceException|NotFoundException
     */
    public function loadRepository(string $widgetType): WidgetConfigRepositoryInterface
    {
        foreach ($this->taggedRepositories as $repository) {
            $this->checkInterface($repository::class, WidgetConfigRepositoryInterface::class);
            if ($widgetType === $repository->getSupportedWidgetType()) {
                return $repository;
            }
        }

        throw new NotFoundException('Widget Repository', $widgetType, 'type');
    }
}
