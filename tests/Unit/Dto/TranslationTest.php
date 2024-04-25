<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Dto;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;

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
