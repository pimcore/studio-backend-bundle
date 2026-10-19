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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DetailDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectDetail;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class DataServiceTest extends Unit
{
    public function testTheEditViewIsHydratedWithoutHistoricalVersionContext(): void
    {
        $adapter = $this->createSpyAdapter();
        $service = $this->createService($adapter);
        $detail = $this->createDetail();

        $service->setObjectDetailData($detail, $this->createObject());

        $this->assertSame(['total' => 'detail'], $detail->getObjectData());
        $this->assertCount(1, $adapter->contexts);
        $this->assertNull($adapter->contexts[0]);
    }

    public function testAVersionIsHydratedAsHistoricalVersion(): void
    {
        $adapter = $this->createSpyAdapter();
        $service = $this->createService($adapter);
        $version = $this->createVersion();

        $service->setObjectDetailData($version, $this->createObject());

        $this->assertSame(['total' => 'detail'], $version->getObjectData());
        $this->assertCount(1, $adapter->contexts);
        $this->assertInstanceOf(FieldContextData::class, $adapter->contexts[0]);
        $this->assertTrue($adapter->contexts[0]->isHistoricalVersion());
        $this->assertNull($adapter->contexts[0]->getContextObject());
    }

    /**
     * @return SetterDataInterface&DetailDataInterface&object{contexts: array<int, FieldContextData|null>}
     */
    private function createSpyAdapter(): SetterDataInterface
    {
        return new class() implements SetterDataInterface, DetailDataInterface {
            /** @var array<int, FieldContextData|null> */
            public array $contexts = [];

            public function getDataForSetter(
                Concrete $element,
                Data $fieldDefinition,
                string $key,
                array $data,
                UserInterface $user,
                ?FieldContextData $contextData = null,
                bool $isPatch = false
            ): mixed {
                return null;
            }

            public function getDetailData(
                Concrete $object,
                mixed $value,
                Data $fieldDefinition,
                ?FieldContextData $contextData = null,
            ): string {
                $this->contexts[] = $contextData;

                return 'detail';
            }
        };
    }

    private function createService(SetterDataInterface $adapter): DataService
    {
        $calculatedValue = new CalculatedValue();
        $calculatedValue->setName('total');
        $class = new ClassDefinition();
        $class->setFieldDefinitions(['total' => $calculatedValue]);

        return new DataService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, ['getById' => $class]),
            $this->makeEmpty(DataAdapterServiceInterface::class, ['tryDataAdapter' => $adapter]),
            $this->makeEmpty(InheritanceServiceInterface::class),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class, ['hasElementWorkflows' => false]),
        );
    }

    private function createObject(): Concrete
    {
        return $this->makeEmpty(Concrete::class, [
            'getClassId' => 'product',
            'get' => null,
            'getModificationDate' => 1,
        ]);
    }

    private function createDetail(): DataObjectDetail
    {
        return new DataObjectDetail(
            'object',
            'Product',
            'object',
            true,
            false,
            false,
            '/object',
            new DataObjectPermissions(
                save: true,
                unpublish: true,
                localizedEdit: 'en',
                localizedView: 'en',
                list: true,
                view: true,
                publish: true,
                delete: true,
                rename: true,
                create: true,
                settings: true,
                versions: true,
                properties: true
            ),
            0,
            'key',
            'ASC',
            false,
            5,
            1,
            '/',
            new ElementIcon(ElementIconTypes::NAME->value, 'class'),
            1,
            1,
            null,
            false,
            1,
            1
        );
    }

    private function createVersion(): DataObjectVersion
    {
        return new DataObjectVersion(
            'object',
            'object',
            false,
            '/object',
            0,
            5,
            1,
            '/',
            new ElementIcon(ElementIconTypes::NAME->value, 'class'),
            1,
            1,
            null,
            false,
            1,
            1,
            'Product',
            true
        );
    }
}
