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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\DeleteInfo;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\BatchDeleteInfoService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\IdsParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class BatchDeleteInfoServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testNoDependenciesAndAllRecyclable(): void
    {
        $service = $this->createService(
            [1 => ['deps' => false, 'recycle' => true], 2 => ['deps' => false, 'recycle' => true]],
            $calls
        );

        $result = $service->getBatchDeleteInfo(new IdsParameter([1, 2]), ElementTypes::TYPE_DATA_OBJECT, $this->makeUser());

        $this->assertFalse($result->getHasDependencies());
        $this->assertTrue($result->getCanUseRecycleBin());
    }

    /**
     * @throws Exception
     */
    public function testHasDependenciesWhenAnyElementHasThem(): void
    {
        $service = $this->createService(
            [1 => ['deps' => false, 'recycle' => true], 2 => ['deps' => true, 'recycle' => true]],
            $calls
        );

        $result = $service->getBatchDeleteInfo(new IdsParameter([1, 2]), ElementTypes::TYPE_DATA_OBJECT, $this->makeUser());

        $this->assertTrue($result->getHasDependencies());
        $this->assertTrue($result->getCanUseRecycleBin());
    }

    /**
     * @throws Exception
     */
    public function testNotRecyclableWhenAnyElementIsNotRecyclable(): void
    {
        $service = $this->createService(
            [1 => ['deps' => false, 'recycle' => true], 2 => ['deps' => false, 'recycle' => false]],
            $calls
        );

        $result = $service->getBatchDeleteInfo(new IdsParameter([1, 2]), ElementTypes::TYPE_DATA_OBJECT, $this->makeUser());

        $this->assertFalse($result->getCanUseRecycleBin());
    }

    /**
     * @throws Exception
     */
    public function testStopsOnceBothResultsAreFinal(): void
    {
        // Element 1 both has dependencies and is not recyclable -> both aggregates are final, so the
        // remaining elements must not be resolved or queried.
        $service = $this->createService(
            [
                1 => ['deps' => true, 'recycle' => false],
                2 => ['deps' => true, 'recycle' => false],
                3 => ['deps' => true, 'recycle' => false],
            ],
            $calls
        );

        $result = $service->getBatchDeleteInfo(new IdsParameter([1, 2, 3]), ElementTypes::TYPE_DATA_OBJECT, $this->makeUser());

        $this->assertSame([1], $calls['resolved']);
        $this->assertSame([1], $calls['info']);
        $this->assertTrue($result->getHasDependencies());
        $this->assertFalse($result->getCanUseRecycleBin());
    }

    /**
     * @throws Exception
     */
    public function testInvalidElementTypeThrows(): void
    {
        $service = $this->createService([1 => ['deps' => false, 'recycle' => true]], $calls);

        $this->expectException(InvalidElementTypeException::class);

        $service->getBatchDeleteInfo(new IdsParameter([1]), 'bogus', $this->makeUser());
    }

    /**
     * @param array<int, array{deps: bool, recycle: bool}> $spec
     * @param array<string, array<int>>                    $calls
     *
     * @throws Exception
     */
    private function createService(array $spec, ?array &$calls = null): BatchDeleteInfoService
    {
        $calls = ['resolved' => [], 'info' => []];

        $elements = [];
        foreach (array_keys($spec) as $id) {
            $elements[$id] = $this->makeEmpty(ElementInterface::class, ['getId' => $id]);
        }

        $elementService = $this->makeEmpty(ElementServiceInterface::class, [
            'getAllowedElementById' => function (string $type, int $id, UserInterface $user) use (&$calls, $elements) {
                $calls['resolved'][] = $id;

                return $elements[$id];
            },
        ]);

        $elementDeleteService = $this->makeEmpty(ElementDeleteServiceInterface::class, [
            'getElementDeleteInfo' => function (ElementInterface $element, UserInterface $user) use (&$calls, $spec) {
                $calls['info'][] = $element->getId();

                return new DeleteInfo($spec[$element->getId()]['deps'], $spec[$element->getId()]['recycle']);
            },
        ]);

        return new BatchDeleteInfoService($elementService, $elementDeleteService);
    }

    /**
     * @throws Exception
     */
    private function makeUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 42]);
    }
}
