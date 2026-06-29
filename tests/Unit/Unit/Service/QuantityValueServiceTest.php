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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Unit\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Unit\Hydrator\QuantityValueHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\CreateUnitParameters;
use Pimcore\Bundle\StudioBackendBundle\Unit\Repository\QuantityValueRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Unit\Service\QuantityValueService;
use Pimcore\Model\DataObject\QuantityValue\Service as QuantityValueModelService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function json_encode;
use function sprintf;
use function str_repeat;

/**
 * @internal
 */
final class QuantityValueServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testCreateInvalidUnit(): void
    {
        $service = $this->createService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unit ID must not exceed 50 characters');

        $service->createUnit(new CreateUnitParameters(str_repeat('a', 51)));
    }

    /**
     * @throws Exception
     */
    public function testCreateUnitWithInvalidCharacters(): void
    {
        $invalidIds = ['m/h', 'test?', '#hashtag', 'm h', 'a+b', 'm%2Fh', ''];

        foreach ($invalidIds as $invalidId) {
            $service = $this->createService();
            $exception = null;

            try {
                $service->createUnit(new CreateUnitParameters($invalidId));
            } catch (InvalidArgumentException $exception) {
            }

            $this->assertInstanceOf(
                InvalidArgumentException::class,
                $exception,
                sprintf('Unit ID "%s" should be rejected.', $invalidId)
            );
            $this->assertStringContainsString('contains invalid characters', $exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testCreateUnitWithValidId(): void
    {
        $validIds = ['mm', 'm_s', 'km-h', 'MM2', 'a'];

        foreach ($validIds as $validId) {
            $repository = $this->makeEmpty(QuantityValueRepositoryInterface::class, [
                'unitExists' => true,
            ]);
            $service = $this->createService(repository: $repository);
            $exception = null;

            try {
                $service->createUnit(new CreateUnitParameters($validId));
            } catch (InvalidArgumentException $exception) {
            }

            // the uniqueness check is only reached when the ID passed the character validation
            $this->assertInstanceOf(
                InvalidArgumentException::class,
                $exception,
                sprintf('Unit ID "%s" should pass character validation.', $validId)
            );
            $this->assertStringContainsString('already exists', $exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testImportUnitsWithInvalidJson(): void
    {
        $service = $this->createService(
            modelService: $this->makeEmpty(QuantityValueModelService::class, [
                'importDefinitionFromJson' => Expected::never(),
            ])
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('valid JSON array');

        $service->importUnits('this is not json');
    }

    /**
     * @throws Exception
     */
    public function testImportUnitsWithNonArrayJson(): void
    {
        $service = $this->createService(
            modelService: $this->makeEmpty(QuantityValueModelService::class, [
                'importDefinitionFromJson' => Expected::never(),
            ])
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('valid JSON array');

        $service->importUnits('"just a string"');
    }

    /**
     * @throws Exception
     */
    public function testImportUnitsWithMissingId(): void
    {
        $service = $this->createService(
            modelService: $this->makeEmpty(QuantityValueModelService::class, [
                'importDefinitionFromJson' => Expected::never(),
            ])
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty string "id"');

        $service->importUnits(json_encode([['abbreviation' => 'mm']]));
    }

    /**
     * @throws Exception
     */
    public function testImportUnitsWithInvalidIds(): void
    {
        $service = $this->createService(
            modelService: $this->makeEmpty(QuantityValueModelService::class, [
                'importDefinitionFromJson' => Expected::never(),
            ])
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Import contains invalid unit IDs: "m/h", "test?"');

        $service->importUnits(json_encode([
            ['id' => 'mm'],
            ['id' => 'm/h'],
            ['id' => 'test?'],
        ]));
    }

    /**
     * @throws Exception
     */
    public function testImportUnitsWithValidIds(): void
    {
        $service = $this->createService(
            modelService: $this->makeEmpty(QuantityValueModelService::class, [
                'importDefinitionFromJson' => Expected::once(true),
            ])
        );

        $service->importUnits(json_encode([
            ['id' => 'mm', 'abbreviation' => 'mm'],
            ['id' => 'm_s'],
            ['id' => 'km-h'],
        ]));
    }

    /**
     * @throws Exception
     */
    public function testImportUnitsFailure(): void
    {
        $service = $this->createService(
            modelService: $this->makeEmpty(QuantityValueModelService::class, [
                'importDefinitionFromJson' => false,
            ])
        );

        $this->expectException(EnvironmentException::class);

        $service->importUnits(json_encode([['id' => 'mm']]));
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?QuantityValueModelService $modelService = null,
        ?QuantityValueRepositoryInterface $repository = null,
    ): QuantityValueService {
        return new QuantityValueService(
            $this->makeEmpty(DownloadServiceInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterMapperServiceInterface::class),
            $this->makeEmpty(QuantityValueHydratorInterface::class),
            $modelService ?? $this->makeEmpty(QuantityValueModelService::class),
            $repository ?? $this->makeEmpty(QuantityValueRepositoryInterface::class),
        );
    }
}
