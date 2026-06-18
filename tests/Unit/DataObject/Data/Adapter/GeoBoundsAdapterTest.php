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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\GeoBoundsAdapter;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geobounds as GeoboundsDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\UserInterface;

/**
 * @internal
 *
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
final class GeoBoundsAdapterTest extends Unit
{
    private const NORTH_EAST = ['latitude' => 52.0, 'longitude' => 13.0];

    private const SOUTH_WEST = ['latitude' => 51.0, 'longitude' => 12.0];

    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithCoordinates(): void
    {
        $result = $this->callAdapter(['bounds' => ['northEast' => self::NORTH_EAST, 'southWest' => self::SOUTH_WEST]]);

        $this->assertInstanceOf(Geobounds::class, $result);
        $this->assertSame(52.0, $result->getNorthEast()->getLatitude());
        $this->assertSame(12.0, $result->getSouthWest()->getLongitude());
    }

    /**
     * Regression test for #18144: a coordinate of 0 must not drop the whole bounds value.
     *
     * @throws Exception
     */
    public function testGetDataForSetterKeepsZeroCoordinates(): void
    {
        $zero = ['latitude' => 0.0, 'longitude' => 0.0];

        $result = $this->callAdapter(['bounds' => ['northEast' => $zero, 'southWest' => $zero]]);

        $this->assertInstanceOf(Geobounds::class, $result);
        $this->assertSame(0.0, $result->getNorthEast()->getLatitude());
        $this->assertSame(0.0, $result->getSouthWest()->getLongitude());
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenCoordinateNull(): void
    {
        $result = $this->callAdapter([
            'bounds' => [
                'northEast' => ['latitude' => null, 'longitude' => 13.0],
                'southWest' => self::SOUTH_WEST,
            ],
        ]);

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenDirectionMissing(): void
    {
        $this->assertNull($this->callAdapter(['bounds' => ['northEast' => self::NORTH_EAST]]));
    }

    /**
     * @throws Exception
     */
    private function callAdapter(array $data): ?Geobounds
    {
        return (new GeoBoundsAdapter())->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(GeoboundsDefinition::class),
            'bounds',
            $data,
            $this->makeEmpty(UserInterface::class)
        );
    }
}
