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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema;

use Codeception\Test\Unit;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use ReflectionClass;

/**
 * @internal
 */
final class KeyLayoutTest extends Unit
{
    public function testTitleIsOptionalAndNullable(): void
    {
        $definition = new Input();

        $this->assertNull((new KeyLayout(42, 'weight', 'The weight', $definition))->getTitle());
        $this->assertSame(
            'Weight',
            (new KeyLayout(42, 'weight', 'The weight', $definition, 'Weight'))->getTitle()
        );

        $attributes = (new ReflectionClass(KeyLayout::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['id', 'name', 'description', 'definition'],
            $attributes[0]->getArguments()['required']
        );
    }
}
