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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Version\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Pimcore\Bundle\StudioBackendBundle\Version\MappedParameter\UpdateVersionParameter;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function str_repeat;

/**
 * The coauthor fields are persisted into VARCHAR(50)/VARCHAR(255) columns, so overlong input has
 * to be rejected by `PUT /versions/{id}` instead of being truncated during the save.
 *
 * @internal
 */
final class UpdateVersionParameterTest extends Unit
{
    private ValidatorInterface $validator;

    protected function _before(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testParameterWithoutCoauthorValuesIsValid(): void
    {
        $violations = $this->validator->validate(new UpdateVersionParameter(public: true, note: 'a note'));

        $this->assertCount(0, $violations);
    }

    public function testEmptyStringsAreValidBecauseTheyClearTheFields(): void
    {
        $violations = $this->validator->validate(
            new UpdateVersionParameter(coauthorType: '', coauthor: '')
        );

        $this->assertCount(0, $violations);
    }

    public function testCoauthorValuesAtTheLengthLimitAreValid(): void
    {
        $violations = $this->validator->validate(new UpdateVersionParameter(
            coauthorType: str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH),
            coauthor: str_repeat('b', VersionCoauthor::MAX_COAUTHOR_LENGTH),
        ));

        $this->assertCount(0, $violations);
    }

    public function testOverlongCoauthorTypeIsRejected(): void
    {
        $violations = $this->validator->validate(new UpdateVersionParameter(
            coauthorType: str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH + 1),
        ));

        $this->assertCount(1, $violations);
        $this->assertSame('coauthorType', $violations->get(0)->getPropertyPath());
    }

    public function testOverlongCoauthorIsRejected(): void
    {
        $violations = $this->validator->validate(new UpdateVersionParameter(
            coauthor: str_repeat('b', VersionCoauthor::MAX_COAUTHOR_LENGTH + 1),
        ));

        $this->assertCount(1, $violations);
        $this->assertSame('coauthor', $violations->get(0)->getPropertyPath());
    }
}
