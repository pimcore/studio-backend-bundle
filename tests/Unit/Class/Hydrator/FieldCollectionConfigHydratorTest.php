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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollectionConfigHydrator;
use Pimcore\Model\DataObject\Fieldcollection\Definition;

/**
 * Regression coverage for PEES-2061: field collection definitions created before Pimcore 10 or
 * imported with "title": null keep a null title, which must hydrate as an empty string
 * instead of failing with a TypeError.
 *
 * @internal
 */
final class FieldCollectionConfigHydratorTest extends Unit
{
    public function testHydrateTreatsMissingTitleAsEmptyString(): void
    {
        $definition = new Definition();
        $definition->setKey('TaxEntry');
        $definition->setTitle(null);

        $config = (new FieldCollectionConfigHydrator())->hydrate($definition);

        $this->assertSame('TaxEntry', $config->getKey());
        $this->assertSame('', $config->getTitle());
    }

    public function testHydrateKeepsExistingTitle(): void
    {
        $definition = new Definition();
        $definition->setKey('TaxEntry');
        $definition->setTitle('Tax entry');

        $config = (new FieldCollectionConfigHydrator())->hydrate($definition);

        $this->assertSame('Tax entry', $config->getTitle());
    }
}
