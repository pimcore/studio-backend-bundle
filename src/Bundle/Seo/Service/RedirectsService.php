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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service;

use Pimcore\Bundle\SeoBundle\Model\Redirect as RedirectModel;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Event\PreResponse\RedirectListEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator\RedirectHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Repository\RedirectsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class RedirectsService implements RedirectsServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private RedirectHydratorInterface $hydrator,
        private RedirectsRepositoryInterface $repository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listRedirects(CollectionFilterParameter $parameters): Collection
    {
        $listing = $this->repository->getListing(
            $this->filterMapper->getFilterParameters($parameters)
        );
        $redirects = $listing->load();
        $list = [];

        foreach ($redirects as $redirect) {
            $list[] = $this->getHydratedRedirect($redirect);
        }

        return new Collection(
            $listing->count(),
            $list
        );
    }

    private function getHydratedRedirect(RedirectModel $redirect): Redirect
    {
        $entry = $this->hydrator->hydrate($redirect);
        $this->eventDispatcher->dispatch(
            new RedirectListEvent($entry),
            RedirectListEvent::EVENT_NAME
        );

        return $entry;
    }
}
