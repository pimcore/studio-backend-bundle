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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;

/**
 * A layout response is reachable without a resolvable user, so the label language falls back
 * instead of the endpoint failing or publishing an unlabelled payload.
 *
 * @internal
 */
final class LabelLanguageResolverTest extends Unit
{
    use LabelResolverMockTrait;

    private const string USER_LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    public function testTheRequestingUsersLanguageIsUsed(): void
    {
        $resolver = $this->createLabelLanguageResolver(self::USER_LANGUAGE, self::DEFAULT_LANGUAGE);

        $this->assertSame(self::USER_LANGUAGE, $resolver->getLabelLanguage());
    }

    public function testWithoutACurrentUserTheInstallationDefaultIsUsed(): void
    {
        $resolver = $this->createLabelLanguageResolver(null, self::DEFAULT_LANGUAGE);

        $this->assertSame(self::DEFAULT_LANGUAGE, $resolver->getLabelLanguage());
    }

    public function testAnEmptyUserLanguageFallsBackToTheInstallationDefault(): void
    {
        $resolver = $this->createLabelLanguageResolver('', self::DEFAULT_LANGUAGE);

        $this->assertSame(self::DEFAULT_LANGUAGE, $resolver->getLabelLanguage());
    }

    /**
     * An installation that configures no default language has no language left to ask for; the
     * resolver still answers, and core's chain then ends at the key's own title.
     */
    public function testWithoutADefaultLanguageTheLanguageIsEmpty(): void
    {
        $resolver = $this->createLabelLanguageResolver(null, null);

        $this->assertSame('', $resolver->getLabelLanguage());
    }
}
