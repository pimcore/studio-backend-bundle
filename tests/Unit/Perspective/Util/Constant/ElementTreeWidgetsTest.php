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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Perspective\Util\Constant;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\ElementTreeWidgets;

/**
 * @internal
 */
final class ElementTreeWidgetsTest extends Unit
{
    public function testValuesReturnsAllDefaultWidgetIds(): void
    {
        $values = ElementTreeWidgets::values();

        $this->assertCount(3, $values);
        $this->assertContains('studio_asset_tree_widget', $values);
        $this->assertContains('studio_data_object_tree_widget', $values);
        $this->assertContains('studio_document_tree_widget', $values);
    }

    public function testEnumCasesMatchExpectedValues(): void
    {
        $this->assertSame('studio_asset_tree_widget', ElementTreeWidgets::DEFAULT_ASSET_TREE->value);
        $this->assertSame('studio_data_object_tree_widget', ElementTreeWidgets::DEFAULT_DATA_OBJECT_TREE->value);
        $this->assertSame('studio_document_tree_widget', ElementTreeWidgets::DEFAULT_DOCUMENT_TREE->value);
    }
}
