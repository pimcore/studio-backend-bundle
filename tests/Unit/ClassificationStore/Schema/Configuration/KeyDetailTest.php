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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema\Configuration;

use Codeception\Test\Unit;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use ReflectionClass;

/**
 * @internal
 */
final class KeyDetailTest extends Unit
{
    public function testTitleIsOptionalAndNullable(): void
    {
        $this->assertNull((new KeyDetail(42, 'weight', 1, 'input', true))->getTitle());
        $this->assertSame(
            'Weight',
            (new KeyDetail(42, 'weight', 1, 'input', true, null, [], null, null, 'Weight'))->getTitle()
        );

        $attributes = (new ReflectionClass(KeyDetail::class))->getAttributes(Schema::class);

        $this->assertSame(
            [
                'id', 'name', 'storeId', 'type', 'enabled', 'description',
                'definition', 'creationDate', 'modificationDate',
            ],
            $attributes[0]->getArguments()['required']
        );
    }
}
