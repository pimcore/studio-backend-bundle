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

use Exception;
use Pimcore\Bundle\SeoBundle\Model\Redirect as RedirectModel;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Event\PreResponse\RedirectListEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Event\PreResponse\RedirectStatusEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator\RedirectHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator\RedirectStatusHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Repository\RedirectsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class RedirectsService implements RedirectsServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private RedirectHydratorInterface $hydrator,
        private RedirectStatusHydratorInterface $statusHydrator,
        private RedirectsRepositoryInterface $repository,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function addRedirect(RedirectAddParameters $parameters): Redirect
    {
        $this->validateType($parameters->getType());

        $redirect = new RedirectModel();
        $redirect->setType($parameters->getType());
        $redirect->setSource($parameters->getSource());
        $redirect->setTarget($this->getTargetValue($parameters->getTarget()));

        try {
            $redirect->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(id: null, error: $e->getMessage(), previous: $e);
        }

        return $this->getHydratedRedirect($redirect);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRedirect(int $id, RedirectUpdateParameters $parameters): Redirect
    {
        $this->validateType($parameters->getType());
        $this->validatePriority($parameters->getPriority());
        $this->validateStatus($parameters->getStatusCode());

        $source = $parameters->getSource();
        if ($source !== null && $parameters->isRegex() === false) {
            $source = str_replace('+', ' ', $source);
        }
        $redirect = $this->repository->getById($id);
        $redirect->setType($parameters->getType());
        $redirect->setSourceSite($parameters->getSourceSite());
        $redirect->setSource($source);
        $redirect->setTargetSite($parameters->getTargetSite());
        $redirect->setTarget($this->getTargetValue($parameters->getTarget()));
        $redirect->setStatusCode($parameters->getStatusCode());
        $redirect->setPriority($parameters->getPriority());
        $redirect->setRegex($parameters->isRegex());
        $redirect->setActive($parameters->isActive());
        $redirect->setPassThroughParameters($parameters->isPassThroughParameters());
        $redirect->setExpiry($parameters->getExpiry());

        try {
            $redirect->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(id: null, error: $e->getMessage(), previous: $e);
        }

        return $this->getHydratedRedirect($redirect);
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

    /**
     * {@inheritdoc}
     */
    public function deleteRedirect(int $id): void
    {
        $redirect = $this->repository->getById($id);
        $redirect->delete();
    }

    public function cleanupRedirects(): void
    {
        $listing = $this->repository->getListing();
        $listing->setCondition('expiry IS NOT NULL AND expiry <' . time());
        $expiredRedirects = $listing->load();

        foreach ($expiredRedirects as $expiredRedirect) {
            $expiredRedirect->delete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listTypes(): array
    {
        return RedirectModel::TYPES;
    }

    /**
     * {@inheritdoc}
     */
    public function listPriorities(): array
    {
        return [1, 2, 3, 4, 5,6, 7, 8, 9, 10, 99];
    }

    /**
     * {@inheritdoc}
     */
    public function listStatuses(): array
    {
        $statuses = RedirectModel::getStatusCodes();
        $entries = [];
        foreach ($statuses as $code => $label) {
            $entry = $this->statusHydrator->hydrate($code, $label);
            $this->eventDispatcher->dispatch(
                new RedirectStatusEvent($entry),
                RedirectStatusEvent::EVENT_NAME
            );
            $entries[] = $entry;
        }

        return $entries;
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

    /**
     * @throws InvalidArgumentException
     */
    private function validateType(string $type): void
    {
        if (!in_array($type, $this->listTypes(), true)) {
            throw new InvalidArgumentException(sprintf('Invalid redirect type: %s', $type));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validatePriority(int $priority): void
    {
        if (!in_array($priority, $this->listPriorities(), true)) {
            throw new InvalidArgumentException(sprintf('Invalid redirect priority: %s', $priority));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateStatus(int $statusCode): void
    {
        if (!array_key_exists($statusCode, RedirectModel::getStatusCodes())) {
            throw new InvalidArgumentException(sprintf('Invalid redirect status: %d', $statusCode));
        }
    }

    private function getTargetValue(?string $target): ?string
    {
        if (empty($target)) {
            return $target;
        }

        $document = $this->serviceResolver->getElementByPath(ElementTypes::TYPE_DOCUMENT, $target);
        if ($document === null) {
            return $target;
        }

        return (string)$document->getId();
    }
}
