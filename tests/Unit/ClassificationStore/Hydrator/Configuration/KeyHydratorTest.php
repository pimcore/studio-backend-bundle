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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Hydrator\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydrator;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * @internal
 */
final class KeyHydratorTest extends Unit
{
    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    private KeyHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new KeyHydrator();
    }

    public function testStoredTitleIsPassedThroughVerbatim(): void
    {
        $keyConfig = $this->createKeyConfig();
        $keyConfig->setTitle('Weight');

        $this->assertSame('Weight', $this->hydrator->hydrateKeyDetail($keyConfig)->getTitle());
    }

    public function testUnsetTitleIsNotSubstitutedWithTheName(): void
    {
        $keyConfig = $this->createKeyConfig();

        $this->assertSame(
            $keyConfig->getTitle(),
            $this->hydrator->hydrateKeyDetail($keyConfig)->getTitle()
        );
    }

    public function testStoredIdentifierIsPassedThroughVerbatim(): void
    {
        $keyConfig = $this->createKeyConfig();
        $keyConfig->setIdentifier(self::IDENTIFIER);

        $this->assertSame(self::IDENTIFIER, $this->hydrator->hydrateKeyDetail($keyConfig)->getIdentifier());
    }

    public function testUnsetIdentifierStaysNull(): void
    {
        $this->assertNull($this->hydrator->hydrateKeyDetail($this->createKeyConfig())->getIdentifier());
    }

    private function createKeyConfig(): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(42);
        $keyConfig->setName('weight');
        $keyConfig->setType('input');

        return $keyConfig;
    }
}
