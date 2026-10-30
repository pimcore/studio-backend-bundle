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
use Pimcore\Bundle\SeoBundle\Redirect\TargetResolverInterface;
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
use Pimcore\Model\Element\ElementType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_key_exists;
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
        private TargetResolverInterface $targetResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function addRedirect(RedirectAddParameters $parameters): Redirect
    {
        $this->validateType($parameters->getType());

        $targetType = $this->getTargetType($parameters->getTargetType());

        $redirect = new RedirectModel();
        $redirect->setType($parameters->getType());
        $redirect->setSource($parameters->getSource());
        $this->applyTarget($redirect, $parameters->getTarget(), $targetType);

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
    public function updateRedirect(int $id, RedirectUpdateParameters $parameters): Redirect
    {
        $this->validateType($parameters->getType());
        $this->validatePriority($parameters->getPriority());
        $this->validateStatus($parameters->getStatusCode());

        $source = $parameters->getSource();
        if ($source !== null && $parameters->isRegex() === false) {
            $source = str_replace('+', ' ', $source);
        }
        $targetType = $this->getTargetType($parameters->getTargetType());

        $redirect = $this->repository->getById($id);
        $redirect->setType($parameters->getType());
        $redirect->setSourceSite($parameters->getSourceSite());
        $redirect->setSource($source);
        $redirect->setTargetSite($parameters->getTargetSite());
        $this->applyTarget($redirect, $parameters->getTarget(), $targetType);
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
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 99];
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

    /**
     * The target is submitted as the element path the API renders, so it is resolved back to
     * the element it names. A target that belongs to no element is kept as a plain (external
     * or not yet existing) URL and has no element type.
     *
     * @throws InvalidArgumentException when the submitted target type names no such element
     */
    private function applyTarget(RedirectModel $redirect, ?string $target, ?ElementType $targetType): void
    {
        // an edit that touches neither the target nor its type must not re-resolve it: the
        // element it points at could have been deleted, or its path could since have been
        // taken by an element of another type. A submitted type that differs is a deliberate
        // request to resolve the same path in another tree.
        $keepsTargetType = $targetType === null || $targetType === $redirect->getTargetType();

        if ($keepsTargetType && $target === $this->renderTarget($redirect)) {
            return;
        }

        if (empty($target)) {
            $redirect->setTarget($target);
            $redirect->setTargetType(null);

            return;
        }

        // a client that sends no type cannot say which tree it meant, so the type is detected;
        // one that does restricts the lookup, which is how an ambiguous path is disambiguated
        $resolved = $targetType !== null
            ? $this->targetResolver->resolveElement($target, $targetType)
            : $this->targetResolver->detectElement($target);

        if ($resolved === null && $targetType !== null) {
            throw new InvalidArgumentException(
                sprintf('No %s usable as a redirect target found at "%s"', $targetType->value, $target)
            );
        }

        $redirect->setTarget($resolved?->getTargetValue() ?? $target);
        $redirect->setTargetType($resolved?->type);
    }

    /**
     * The target as the API renders it, i.e. what a client sends back unchanged.
     */
    private function renderTarget(RedirectModel $redirect): ?string
    {
        $target = $redirect->getTarget();

        if ($target === null) {
            return null;
        }

        return $this->targetResolver->resolvePath($target, $redirect->getTargetType()) ?? $target;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getTargetType(?string $targetType): ?ElementType
    {
        // clients that do not know about target types leave the type to be detected
        if ($targetType === null || $targetType === '') {
            return null;
        }

        return ElementType::tryFrom($targetType) ?? throw new InvalidArgumentException(
            sprintf('Invalid redirect target type: %s', $targetType)
        );
    }
}
