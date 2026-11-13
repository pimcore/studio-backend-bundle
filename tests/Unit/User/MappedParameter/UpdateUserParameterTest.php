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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserWelcomeScreen;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function str_repeat;

/**
 * The welcome screen path is stored in a VARCHAR(255) column, so overlong input has to be
 * rejected by `PUT /user/{id}` instead of failing or being truncated during the save.
 *
 * @internal
 */
final class UpdateUserParameterTest extends Unit
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
        $this->assertNull($this->createParameter()->getWelcomeScreenPath());
    }

    public function testWelcomeScreenPathAtTheLengthLimitIsAccepted(): void
    {
        $path = str_repeat('a', UserWelcomeScreen::MAX_PATH_LENGTH);
        $parameter = $this->createParameter($path);

        $this->assertCount(0, $this->validator->validate($parameter));
        $this->assertSame($path, $parameter->getWelcomeScreenPath());
    }

    public function testEmptyWelcomeScreenPathIsValidBecauseItClearsThePath(): void
    {
        $this->assertCount(0, $this->validator->validate($this->createParameter('')));
    }

    public function testOverlongWelcomeScreenPathIsRejected(): void
    {
        $violations = $this->validator->validate(
            $this->createParameter(str_repeat('a', UserWelcomeScreen::MAX_PATH_LENGTH + 1))
        );

        $this->assertCount(1, $violations);
        $this->assertSame('welcomeScreenPath', $violations->get(0)->getPropertyPath());
    }

    private function createParameter(?string $welcomeScreenPath = null): UpdateUserParameter
    {
        return new UpdateUserParameter(
            email: null,
            firstname: null,
            lastname: null,
            active: true,
            admin: false,
            classes: [],
            docTypes: [],
            closeWarning: false,
            allowDirtyClose: false,
            contentLanguages: [],
            keyBindings: [],
            language: 'en',
            dateTimeLocale: null,
            memorizeTabs: false,
            parentId: 0,
            permissions: [],
            roles: [],
            twoFactorAuthenticationRequired: false,
            websiteTranslationLanguagesEdit: [],
            websiteTranslationLanguagesView: [],
            welcomeScreen: true,
            assetWorkspaces: [],
            dataObjectWorkspaces: [],
            documentWorkspaces: [],
            welcomeScreenPath: $welcomeScreenPath,
        );
    }
}
