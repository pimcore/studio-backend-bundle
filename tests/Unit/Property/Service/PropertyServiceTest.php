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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Property\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Property\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\DraftElementResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\VersionDraftElementResolver;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydrator;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyService;
use Pimcore\Bundle\StudioBackendBundle\Resolver\Element\ReferenceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Property\Predefined;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class PropertyServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testCreatePredefinedPropertyHydratesRepositoryResultDirectly(): void
    {
        $predefined = $this->getPredefined();

        $repository = $this->makeEmpty(PropertyRepositoryInterface::class, [
            'createPredefinedProperty' => $predefined,
            'getPredefinedProperty' => Expected::never(),
        ]);

        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::once(static fn (object $event): object => $event),
        ]);

        $service = $this->createService(repository: $repository, eventDispatcher: $eventDispatcher);

        $result = $service->createPredefinedProperty();

        $this->assertSame('new_id', $result->getId());
        $this->assertSame('New Description', $result->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePredefinedPropertyHydratesRepositoryResultDirectly(): void
    {
        $predefined = $this->getPredefined();
        $predefined->setDescription('Updated Description');

        $repository = $this->makeEmpty(PropertyRepositoryInterface::class, [
            'updatePredefinedProperty' => $predefined,
            'getPredefinedProperty' => Expected::never(),
        ]);

        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::once(static fn (object $event): object => $event),
        ]);

        $service = $this->createService(repository: $repository, eventDispatcher: $eventDispatcher);

        $result = $service->updatePredefinedProperty('new_id', $this->getUpdatePayload());

        $this->assertSame('new_id', $result->getId());
        $this->assertSame('Updated Description', $result->getDescription());
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?PropertyRepositoryInterface $repository = null,
        ?PropertyHydratorInterface $hydrator = null,
        ?SecurityServiceInterface $securityService = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?DraftElementResolverInterface $draftElementResolver = null,
    ): PropertyService {
        return new PropertyService(
            $repository ?? $this->makeEmpty(PropertyRepositoryInterface::class),
            $hydrator ?? $this->realHydrator(),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
            $draftElementResolver ?? new VersionDraftElementResolver(),
        );
    }

    /**
     * @throws Exception
     */
    private function realHydrator(): PropertyHydratorInterface
    {
        return new PropertyHydrator(
            $this->makeEmpty(PredefinedResolverInterface::class),
            $this->makeEmpty(ReferenceResolverInterface::class)
        );
    }

    private function getPredefined(): Predefined
    {
        $property = new Predefined();
        $property->setId('new_id');
        $property->setCtype(ElementTypes::TYPE_DOCUMENT);
        $property->setName('New Property');
        $property->setKey('new_key');
        $property->setType('text');
        $property->setCreationDate(time());
        $property->setModificationDate(time());
        $property->setInheritable(true);
        $property->setDescription('New Description');

        return $property;
    }

    private function getUpdatePayload(): UpdatePredefinedProperty
    {
        return new UpdatePredefinedProperty(
            'New Property',
            'Updated Description',
            'new_key',
            'text',
            null,
            null,
            ElementTypes::TYPE_DOCUMENT,
            true
        );
    }
}
