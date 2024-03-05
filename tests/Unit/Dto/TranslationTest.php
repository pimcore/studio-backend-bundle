<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\Dto;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioApiBundle\Dto\Translation;

final class TranslationTest extends Unit
{
    public function testTranslation(): void
    {
        $translation = new Translation('en', ['login']);
        $this->assertSame('en', $translation->getLocale());
        $this->assertIsArray($translation->getKeys());
        $this->assertCount(1, $translation->getKeys());
        $this->assertContains('login', $translation->getKeys());
    }
}
