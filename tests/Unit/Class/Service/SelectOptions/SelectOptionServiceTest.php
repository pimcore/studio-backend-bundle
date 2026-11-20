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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service\SelectOptions;

use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\SelectOptions\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption\DetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\SelectOptionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions\SelectOptionService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function json_decode;
use function json_encode;

/**
 * @internal
 */
final class SelectOptionServiceTest extends Unit
{
    public function testExportReturnsTheConfigurationAsPrettyPrintedJsonFile(): void
    {
        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getById')
            ->with('EventStatus')
            ->willReturn($this->createConfig());

        $export = $this->createService($repository)->exportSelectOption('EventStatus');

        $this->assertSame('select_option_EventStatus_export.json', $export->getFileName());
        $this->assertStringContainsString("{\n    \"id\": \"EventStatus\",", $export->getJson());
        $this->assertSame(
            [
                'id' => 'EventStatus',
                'group' => 'events',
                'adminOnly' => false,
                'useTraits' => 'App\Model\StatusTrait',
                'implementsInterfaces' => '',
                'selectOptions' => [
                    ['value' => 'open', 'label' => 'Open', 'name' => 'Open'],
                    ['value' => 'closed', 'label' => 'Closed', 'name' => ''],
                ],
            ],
            json_decode($export->getJson(), true)
        );
    }

    public function testImportReplacesTheConfigurationAndKeepsTheTargetId(): void
    {
        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getById')
            ->with('EventStatus')
            ->willReturn($this->createConfig());
        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Config $config): bool {
                return $config->getId() === 'EventStatus'
                    && $config->getGroup() === 'imported'
                    && $config->getAdminOnly() === true
                    && $config->getUseTraits() === ''
                    && $config->getSelectOptionsAsData() === [['value' => 'new', 'label' => 'New', 'name' => '']];
            }));

        $detail = $this->createDetail();
        $result = $this->createService($repository, $detail)->importSelectOptionFromJson(
            'EventStatus',
            '{"id": "OtherStatus", "group": "imported", "adminOnly": true, "useTraits": "",'
            . ' "implementsInterfaces": "", "selectOptions": [{"value": "new", "label": "New"}]}'
        );

        $this->assertSame($detail, $result);
    }

    public function testExportedFileCanBeImportedAgain(): void
    {
        $config = $this->createConfig();
        $savedConfig = null;

        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->expects($this->exactly(2))
            ->method('getById')
            ->with('EventStatus')
            ->willReturn($config);
        $repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Config $config) use (&$savedConfig): void {
                $savedConfig = $config;
            });

        $service = $this->createService($repository);
        $service->importSelectOptionFromJson('EventStatus', $service->exportSelectOption('EventStatus')->getJson());

        $this->assertSame(json_encode($config), json_encode($savedConfig));
    }

    public function testImportRejectsInvalidJson(): void
    {
        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createConfig());
        $repository->expects($this->never())->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uploaded file is not valid JSON');

        $this->createService($repository)->importSelectOptionFromJson('EventStatus', '{not json');
    }

    #[DataProvider('notAnExportProvider')]
    public function testImportRejectsFilesThatAreNotASelectOptionsExport(string $json): void
    {
        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createConfig());
        $repository->expects($this->never())->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uploaded file is not a select options export: missing "selectOptions".');

        $this->createService($repository)->importSelectOptionFromJson('EventStatus', $json);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAnExportProvider(): array
    {
        return [
            'class definition export' => ['{"id": "EventStatus", "layoutDefinitions": null}'],
            'scalar' => ['"EventStatus"'],
            'empty list' => ['[]'],
        ];
    }

    #[DataProvider('malformedImportProvider')]
    public function testImportRejectsMalformedValuesWithoutSaving(string $json, string $expectedMessage): void
    {
        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createConfig());
        $repository->expects($this->never())->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->createService($repository)->importSelectOptionFromJson('EventStatus', $json);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function malformedImportProvider(): array
    {
        return [
            'adminOnly as string' => [
                '{"adminOnly": "yes", "selectOptions": []}',
                'Invalid value for "adminOnly": expected boolean, got string.',
            ],
            'group as number' => [
                '{"group": 1, "selectOptions": []}',
                'Invalid value for "group": expected string or NULL, got integer.',
            ],
            'selectOptions as object' => [
                '{"selectOptions": {"open": {"value": "open"}}}',
                'Invalid value for "selectOptions": expected a list of objects.',
            ],
            'option as string' => [
                '{"selectOptions": ["open"]}',
                'Invalid value for "selectOptions[0]": expected array, got string.',
            ],
            'option value as number' => [
                '{"selectOptions": [{"value": 1}]}',
                'Invalid value for "selectOptions[0].value": expected string, got integer.',
            ],
            'option without value' => [
                '{"selectOptions": [{"label": "Open"}]}',
                'Select option at index 0 must have a non-empty "value" field',
            ],
        ];
    }

    public function testImportRejectsAConfigurationThatIsNotWriteable(): void
    {
        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createConfig());
        $repository->method('save')->willThrowException(new NotWriteableException('Select Option'));

        $hydrator = $this->createMock(DetailHydratorInterface::class);
        $hydrator->expects($this->never())->method('hydrate');

        $this->expectException(NotWriteableException::class);

        $this->createService(repository: $repository, hydrator: $hydrator)->importSelectOptionFromJson(
            'EventStatus',
            '{"selectOptions": [{"value": "open"}]}'
        );
    }

    public function testImportRejectsAdminOnlyConfigurationForNonAdminUsers(): void
    {
        $repository = $this->createMock(SelectOptionRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createConfig(adminOnly: true));
        $repository->expects($this->never())->method('save');

        $this->expectException(ForbiddenException::class);

        $this->createService(repository: $repository, isAdmin: false)->importSelectOptionFromJson(
            'EventStatus',
            '{"selectOptions": [{"value": "open"}]}'
        );
    }

    private function createConfig(bool $adminOnly = false): Config
    {
        return Config::createFromData([
            Config::PROPERTY_ID => 'EventStatus',
            Config::PROPERTY_GROUP => 'events',
            Config::PROPERTY_ADMIN_ONLY => $adminOnly,
            Config::PROPERTY_USE_TRAITS => 'App\Model\StatusTrait',
            Config::PROPERTY_SELECT_OPTIONS => [
                ['value' => 'open', 'label' => 'Open', 'name' => 'Open'],
                ['value' => 'closed', 'label' => 'Closed'],
            ],
        ]);
    }

    private function createDetail(): SelectOptionDetail
    {
        return new SelectOptionDetail(
            'EventStatus',
            'imported',
            true,
            '',
            '',
            [],
            'Pimcore\Model\DataObject\SelectOptions\EventStatus',
            true
        );
    }

    private function createService(
        SelectOptionRepositoryInterface $repository,
        ?SelectOptionDetail $detail = null,
        ?DetailHydratorInterface $hydrator = null,
        bool $isAdmin = true,
    ): SelectOptionService {
        return new SelectOptionService(
            $repository,
            $this->makeEmpty(ConfigResolverInterface::class, [
                'createFromData' => static fn (array $data): Config => Config::createFromData($data),
            ]),
            $hydrator ?? $this->makeEmpty(DetailHydratorInterface::class, [
                'hydrate' => $detail ?? $this->createDetail(),
            ]),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => $isAdmin]),
            ]),
        );
    }
}
