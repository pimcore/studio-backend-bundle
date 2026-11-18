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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Configuration;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\KeyCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The group whose assigned keys are to be left out travels from the payload to the listing, and the
 * total the collection reports is the listing's own - the narrowed one - and not a count taken
 * before the narrowing.
 *
 * @internal
 */
final class KeyCollectionGroupExclusionTest extends Unit
{
    private const int STORE_ID = 3;

    private const int GROUP_ID = 12;

    /**
     * @throws Exception
     */
    public function testTheGroupFromThePayloadReachesTheListing(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getListing')
            ->with($this->anything(), self::STORE_ID, false, self::GROUP_ID)
            ->willReturn($this->listing(0));

        $this->createService($repository)->listKeys(
            new KeyCollectionParameters(excludeKeysOfGroupId: self::GROUP_ID),
            self::STORE_ID
        );
    }

    /**
     * @throws Exception
     */
    public function testAPayloadWithoutTheGroupAsksForEveryKeyOfTheStore(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getListing')
            ->with($this->anything(), self::STORE_ID, false, null)
            ->willReturn($this->listing(0));

        $this->createService($repository)->listKeys(new KeyCollectionParameters(), self::STORE_ID);
    }

    /**
     * @throws Exception
     */
    public function testTheExclusionAndTheDisabledViewAreBothPassedOn(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getListing')
            ->with($this->anything(), self::STORE_ID, true, self::GROUP_ID)
            ->willReturn($this->listing(0));

        $this->createService($repository)->listKeys(
            new KeyCollectionParameters(disabledOnly: true, excludeKeysOfGroupId: self::GROUP_ID),
            self::STORE_ID
        );
    }

    /**
     * @throws Exception
     */
    public function testTheReportedTotalIsTheNarrowedListingsOwn(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('getListing')
            ->willReturn($this->listing(5));

        $collection = $this->createService($repository)->listKeys(
            new KeyCollectionParameters(excludeKeysOfGroupId: self::GROUP_ID),
            self::STORE_ID
        );

        $this->assertSame(5, $collection->getTotalItems());
    }

    /**
     * `load()` and `getTotalCount()` are magic on a Pimcore listing, so a mock cannot stub them.
     */
    private function listing(int $totalCount): Listing
    {
        return new class($totalCount) extends Listing {
            public function __construct(private readonly int $totalCount)
            {
            }

            public function __call(string $method, array $args): mixed
            {
                return $method === 'load' ? [] : $this->totalCount;
            }
        };
    }

    /**
     * @throws Exception
     */
    private function createService(KeyRepositoryInterface $repository): KeyService
    {
        $keyHydrator = $this->createMock(KeyHydratorInterface::class);
        $keyHydrator
            ->method('hydrateKeyDetail')
            ->willReturn(new KeyDetail(1, 'key', self::STORE_ID, 'input', true));

        $filterMapper = $this->createMock(FilterMapperServiceInterface::class);
        $filterMapper
            ->method('getFilterParameters')
            ->willReturn(new FilterParameter());

        return new KeyService(
            $this->createMock(EventDispatcherInterface::class),
            $filterMapper,
            $this->createMock(KeyRelationResolverInterface::class),
            $repository,
            $keyHydrator,
            $this->createMock(SearchIndexRemapTriggerInterface::class),
        );
    }
}
