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
    /**
     * GHSA-pg5f-74vr-w237: as the target of a relation, an element the user may not view is only
     * identified by the id and type the user supplied.
     */
    public function testRestrictedDataIsWithheldWithoutViewAccess(): void
    {
        $data = (new RelatedElementData(83, 'object', 'Product', '/restricted/confidential/element', true, false))
            ->withoutRestrictedData();

        $this->assertSame('', $data->getSubtype());
        $this->assertSame('', $data->getFullPath());
        $this->assertNull($data->getIsPublished());
        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame(83, $data->getId());
        $this->assertSame('object', $data->getType());
    }

    public function testDataIsKeptWithViewAccess(): void
    {
        $data = new RelatedElementData(83, 'object', 'Product', '/path/to/element', true, true);

        $this->assertSame($data, $data->withoutRestrictedData());
        $this->assertSame('Product', $data->getSubtype());
        $this->assertSame('/path/to/element', $data->getFullPath());
        $this->assertTrue($data->getIsPublished());
    }

    /**
     * The constructor itself withholds nothing: a reference that belongs to a configuration (the root
     * folder of a tree widget, an error page) must keep its path for a user who can only navigate
     * through the element.
     */
    public function testConstructorKeepsDataWithoutViewAccess(): void
    {
        $data = new RelatedElementData(83, 'object', 'folder', '/Products/Chucking Equipment', null, false);

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame('folder', $data->getSubtype());
        $this->assertSame('/Products/Chucking Equipment', $data->getFullPath());
    }

    public function testHasViewAccessDefaultsToTrue(): void
    {
        $data = new RelatedElementData(83, 'object', 'folder', '/');

        $this->assertTrue($data->getHasViewAccess());
        $this->assertSame('/', $data->getFullPath());
    }
}
