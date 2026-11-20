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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Schema;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;

/**
 * @internal
 */
final class RelatedElementDataTest extends Unit
{
    public function testFieldsAreKeptWhenUserHasViewAccess(): void
    {
        $data = new RelatedElementData(
            83,
            'object',
            'Product',
            '/path/to/element',
            true,
            true
        );

        $this->assertSame('Product', $data->getSubtype());
        $this->assertSame('/path/to/element', $data->getFullPath());
        $this->assertTrue($data->getIsPublished());
        $this->assertTrue($data->getHasViewAccess());
    }

    /**
     * GHSA-pg5f-74vr-w237: subtype, full path and publish state must be withheld once
     * hasViewAccess is false.
     */
    public function testSensitiveFieldsAreWithheldWhenUserHasNoViewAccess(): void
    {
        $data = new RelatedElementData(
            83,
            'object',
            'Product',
            '/restricted/confidential/element',
            true,
            false
        );

        $this->assertSame('', $data->getSubtype());
        $this->assertSame('', $data->getFullPath());
        $this->assertNull($data->getIsPublished());
        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame(83, $data->getId());
        $this->assertSame('object', $data->getType());
    }

    public function testHasViewAccessDefaultsToTrueAndKeepsFields(): void
    {
        $data = new RelatedElementData(83, 'object', 'folder', '/');

        $this->assertTrue($data->getHasViewAccess());
        $this->assertSame('folder', $data->getSubtype());
        $this->assertSame('/', $data->getFullPath());
    }
}
