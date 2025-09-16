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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Dto;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;

final class TranslationTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation
     */
    public function testTranslation(): void
    {
        $translation = new Translation('en', ['login']);
        $this->assertSame('en', $translation->getLocale());
        $this->assertIsArray($translation->getKeys());
        $this->assertCount(1, $translation->getKeys());
        $this->assertContains('login', $translation->getKeys());
    }
}
