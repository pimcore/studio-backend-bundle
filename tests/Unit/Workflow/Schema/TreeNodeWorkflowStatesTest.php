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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Schema;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowStatesAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\WorkflowStatesTrait;
use function is_subclass_of;

/**
 * @internal
 */
final class TreeNodeWorkflowStatesTest extends Unit
{
    /**
     * @dataProvider treeNodeSchemaProvider
     */
    public function testTreeNodeSchemasCarryWorkflowStates(string $schemaClass): void
    {
        $this->assertTrue(is_subclass_of($schemaClass, WorkflowStatesAwareInterface::class));
    }

    public function testWorkflowStatesAreNullUntilATreeListingSetsThem(): void
    {
        $schema = new class() {
            use WorkflowStatesTrait;
        };

        $this->assertNull($schema->getWorkflowStates());

        $schema->setWorkflowStates([]);

        $this->assertSame([], $schema->getWorkflowStates());
    }

    public static function treeNodeSchemaProvider(): array
    {
        return [
            'asset' => [Asset::class],
            'data object' => [DataObject::class],
            'document' => [Document::class],
        ];
    }
}
