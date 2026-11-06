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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Schema;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Entity\User\UserSettings;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UpdateUserProfile;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function str_repeat;

/**
 * The profile payload is mapped straight onto this schema, so it carries the length limit of
 * the welcome screen path column itself.
 *
 * @internal
 */
final class UpdateUserProfileTest extends Unit
{
    private ValidatorInterface $validator;

    protected function _before(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testWelcomeScreenPathDefaultsToNull(): void
    {
        $this->assertNull($this->createProfile()->getWelcomeScreenPath());
    }

    public function testWelcomeScreenPathAtTheLengthLimitIsAccepted(): void
    {
        $path = str_repeat('a', UserSettings::WELCOME_SCREEN_PATH_MAX_LENGTH);
        $profile = $this->createProfile($path);

        $this->assertCount(0, $this->validator->validate($profile));
        $this->assertSame($path, $profile->getWelcomeScreenPath());
    }

    public function testOverlongWelcomeScreenPathIsRejected(): void
    {
        $violations = $this->validator->validate(
            $this->createProfile(str_repeat('a', UserSettings::WELCOME_SCREEN_PATH_MAX_LENGTH + 1))
        );

        $this->assertCount(1, $violations);
        $this->assertSame('welcomeScreenPath', $violations->get(0)->getPropertyPath());
    }

    private function createProfile(?string $welcomeScreenPath = null): UpdateUserProfile
    {
        return new UpdateUserProfile(
            firstname: null,
            lastname: null,
            email: null,
            language: 'en',
            dateTimeLocale: null,
            welcomeScreen: true,
            memorizeTabs: false,
            contentLanguages: [],
            keyBindings: [],
            welcomeScreenPath: $welcomeScreenPath,
        );
    }
}
