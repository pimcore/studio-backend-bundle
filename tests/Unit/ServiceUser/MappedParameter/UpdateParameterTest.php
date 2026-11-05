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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\UpdateParameter;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

/**
 * A service user has no password, no admin rights and no two-factor settings, and it keeps its
 * name (MCP server access is matched by name). The update payload refuses these fields with a
 * validation error instead of ignoring them.
 *
 * @internal
 */
final class UpdateParameterTest extends Unit
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function forbiddenFields(): iterable
    {
        yield 'password' => [['password' => 'Secret-value-123'], 'password'];
        yield 'admin' => [['admin' => true], 'admin'];
        yield 'admin false' => [['admin' => false], 'admin'];
        yield 'two-factor required' => [['twoFactorAuthenticationRequired' => true], 'twoFactorAuthenticationRequired'];
        yield 'two-factor data' => [['twoFactorAuthentication' => ['enabled' => true]], 'twoFactorAuthentication'];
        yield 'rename' => [['name' => 'svc-other'], 'name'];
    }

    /**
     * @dataProvider forbiddenFields
     *
     * @param array<string, mixed> $field
     */
    public function testAFieldThatIsNoServiceUserFieldIsRefused(array $field, string $path): void
    {
        $parameter = $this->denormalize(['permissions' => ['assets'], ...$field]);
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($parameter);

        $this->assertCount(1, $violations);
        $this->assertSame($path, $violations->get(0)->getPropertyPath());
        $this->assertSame([$path], $parameter->getRefusedFields());
    }

    public function testTheDeclaredFieldsAreAccepted(): void
    {
        $parameter = $this->denormalize([
            'email' => 'erp-team@example.com',
            'permissions' => ['assets'],
            'roles' => [5],
        ]);

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($parameter);

        $this->assertCount(0, $violations);
        $this->assertSame([], $parameter->getRefusedFields());
        $this->assertSame('erp-team@example.com', $parameter->getEmail());
        $this->assertSame(['assets'], $parameter->getPermissions());
        $this->assertSame([5], $parameter->getRoles());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function denormalize(array $data): UpdateParameter
    {
        $propertyInfo = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
        $serializer = new Serializer([
            new ArrayDenormalizer(),
            new ObjectNormalizer(propertyTypeExtractor: $propertyInfo),
        ]);

        return $serializer->denormalize($data, UpdateParameter::class);
    }
}
