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
    private const string SUB_GROUP = '01JBZ8QF6K3M4N5P6R7S8T9V0W';

    private const string NAME = 'weight';

    private const string DESCRIPTION = 'The weight';

    private const string TITLE = 'Weight';

    public function testTitleIsOptionalAndNullable(): void
    {
        $definition = new Input();

        $this->assertNull((new KeyLayout(42, self::NAME, self::DESCRIPTION, $definition))->getTitle());
        $this->assertSame(
            self::TITLE,
            (new KeyLayout(42, self::NAME, self::DESCRIPTION, $definition, self::TITLE))->getTitle()
        );

        $attributes = (new ReflectionClass(KeyLayout::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['id', 'name', 'description', 'definition'],
            $attributes[0]->getArguments()['required']
        );
    }

    public function testSubGroupIsExposed(): void
    {
        $definition = new Input();

        $this->assertNull((new KeyLayout(42, self::NAME, self::DESCRIPTION, $definition))->getSubGroup());
        $this->assertSame(
            self::SUB_GROUP,
            (new KeyLayout(42, self::NAME, self::DESCRIPTION, $definition, self::TITLE, self::SUB_GROUP))
                ->getSubGroup()
        );
    }
}
