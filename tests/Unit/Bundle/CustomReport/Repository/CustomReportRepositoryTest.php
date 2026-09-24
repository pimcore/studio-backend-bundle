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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\CustomReport\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\CustomReportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository\CustomReportRepository;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Util\TransferableProperties;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final class CustomReportRepositoryTest extends Unit
{
    public function testExtractTransferableDataContainsEveryTransferablePropertyAndNothingElse(): void
    {
        $data = $this->createRepository()->extractTransferableData($this->createConfiguredReport());

        $this->assertEqualsCanonicalizing(TransferableProperties::names(), array_keys($data));
        $this->assertArrayNotHasKey('creationDate', $data);
        $this->assertArrayNotHasKey('modificationDate', $data);
        $this->assertArrayNotHasKey('sharedUserIds', $data);
        $this->assertArrayNotHasKey('sharedRoleIds', $data);
    }

    public function testExtractedDataSurvivesRoundTripIntoNewReport(): void
    {
        $repository = $this->createRepository();
        $source = $this->createConfiguredReport();

        $data = $repository->extractTransferableData($source);
        $data['name'] = 'clonedReport';
        $target = $repository->applyTransferableData(new Config(), $data);

        $this->assertSame('clonedReport', $target->getName());
        $this->assertSame($source->getSql(), $target->getSql());
        $this->assertEquals($source->getDataSourceConfig(), $target->getDataSourceConfig());
        $this->assertSame($data['columnConfiguration'], $target->getColumnConfiguration());
        $this->assertSame($source->getNiceName(), $target->getNiceName());
        $this->assertSame($source->getGroup(), $target->getGroup());
        $this->assertSame($source->getGroupIconClass(), $target->getGroupIconClass());
        $this->assertSame($source->getIconClass(), $target->getIconClass());
        $this->assertSame($source->getMenuShortcut(), $target->getMenuShortcut());
        $this->assertSame($source->getReportClass(), $target->getReportClass());
        $this->assertSame($source->getChartType(), $target->getChartType());
        $this->assertSame($source->getPieColumn(), $target->getPieColumn());
        $this->assertSame($source->getPieLabelColumn(), $target->getPieLabelColumn());
        $this->assertSame($source->getXAxis(), $target->getXAxis());
        $this->assertSame($source->getYAxis(), $target->getYAxis());
        $this->assertSame($source->getShareGlobally(), $target->getShareGlobally());
        $this->assertSame($source->getPagination(), $target->getPagination());
        $this->assertSame($source->getSharedUserNames(), $target->getSharedUserNames());
        $this->assertSame($source->getSharedRoleNames(), $target->getSharedRoleNames());
        $this->assertNull($target->getCreationDate());
        $this->assertNull($target->getModificationDate());
    }

    public function testExtractTransferableDataFillsMissingColumnFlags(): void
    {
        $config = new Config();
        $config->setName('legacyReport');
        $config->setColumnConfiguration([['name' => 'id', 'display' => true, 'export' => true]]);

        $data = $this->createRepository()->extractTransferableData($config);

        $this->assertSame(
            [['name' => 'id', 'display' => true, 'export' => true, 'order' => false]],
            $data['columnConfiguration']
        );
    }

    public function testApplyTransferableDataIgnoresUnknownProperties(): void
    {
        $target = $this->createRepository()->applyTransferableData(new Config(), [
            'name' => 'report',
            'creationDate' => 123,
            'modificationDate' => 456,
            'unknownProperty' => 'ignored',
        ]);

        $this->assertSame('report', $target->getName());
        $this->assertNull($target->getCreationDate());
        $this->assertNull($target->getModificationDate());
    }

    private function createRepository(): CustomReportRepository
    {
        return new CustomReportRepository(
            $this->createMock(SecurityServiceInterface::class),
            $this->createMock(CustomReportResolverInterface::class)
        );
    }

    private function createConfiguredReport(): Config
    {
        $config = new Config();
        $config->setName('sourceReport');
        $config->setSql('SELECT 1');
        $config->setDataSourceConfig([['type' => 'sql', 'sql' => 'SELECT a.id', 'from' => 'FROM assets a']]);
        $config->setColumnConfiguration([['name' => 'id', 'display' => true, 'export' => true, 'order' => true]]);
        $config->setNiceName('Source Report');
        $config->setGroup('assets');
        $config->setGroupIconClass('pimcore_icon_asset');
        $config->setIconClass('pimcore_icon_report');
        $config->setMenuShortcut(false);
        $config->setReportClass('App\\Report\\Custom');
        $config->setChartType('pie');
        $config->setPieColumn('count');
        $config->setPieLabelColumn('label');
        $config->setXAxis('date');
        $config->setYAxis(['count', 'total']);
        $config->setShareGlobally(false);
        $config->setPagination(false);
        $config->setSharedUserNames(['editor']);
        $config->setSharedRoleNames(['reporting']);
        $config->setCreationDate(1700000000);
        $config->setModificationDate(1700000001);

        return $config;
    }
}
