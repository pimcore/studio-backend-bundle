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
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Controller\UpdateController;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\UpdateParameter;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use function json_encode;

/**
 * A service user has no password, no admin rights, no two-factor settings and no active flag
 * here, and it keeps its name (MCP server access is matched by name). The update endpoint
 * maps its payload with the context the controller declares, which refuses every key the
 * payload does not declare, whatever its value, instead of ignoring it.
 *
 * @internal
 */
final class UpdateParameterTest extends Unit
{
    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function undeclaredFields(): iterable
    {
        yield 'password' => [['password' => 'Secret-value-123']];
        yield 'admin' => [['admin' => true]];
        yield 'admin false' => [['admin' => false]];
        yield 'two-factor required' => [['twoFactorAuthenticationRequired' => true]];
        yield 'two-factor data' => [['twoFactorAuthentication' => ['enabled' => true]]];
        yield 'rename' => [['name' => 'svc-other']];
        yield 'name null' => [['name' => null]];
        yield 'admin null' => [['admin' => null]];
        yield 'active' => [['active' => false]];
        yield 'misspelled permissions' => [['permission' => ['assets']]];
    }

    /**
     * @dataProvider undeclaredFields
     *
     * @param array<string, mixed> $field
     */
    public function testAKeyThePayloadDoesNotDeclareIsRefused(array $field): void
    {
        $this->expectException(ExtraAttributesException::class);
        $this->deserialize(['permissions' => ['assets'], ...$field]);
    }

    public function testTheDeclaredFieldsAreAccepted(): void
    {
        $parameter = $this->deserialize([
            'email' => 'erp-team@example.com',
            'permissions' => ['assets'],
            'roles' => [5],
        ]);

        $this->assertSame('erp-team@example.com', $parameter->getEmail());
        $this->assertSame(['assets'], $parameter->getPermissions());
        $this->assertSame([5], $parameter->getRoles());
    }

    /**
     * Studio sends the workspaces back as it read them, so their keys must pass too.
     */
    public function testWorkspacesAsReadAreAccepted(): void
    {
        $workspace = [
            'cid' => 1,
            'cpath' => '/',
            'list' => true,
            'view' => true,
            'publish' => false,
            'delete' => false,
            'rename' => false,
            'create' => false,
            'settings' => false,
            'versions' => false,
            'properties' => false,
        ];

        $parameter = $this->deserialize(['assetWorkspaces' => [$workspace]]);

        $this->assertCount(1, $parameter->getAssetWorkspaces());
        $this->assertSame('/', $parameter->getAssetWorkspaces()[0]->getCpath());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deserialize(array $data): UpdateParameter
    {
        $propertyInfo = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
        $serializer = new Serializer(
            [
                new ArrayDenormalizer(),
                new ObjectNormalizer(
                    classMetadataFactory: new ClassMetadataFactory(new AttributeLoader()),
                    propertyTypeExtractor: $propertyInfo
                ),
            ],
            [new JsonEncoder()]
        );

        return $serializer->deserialize(
            (string) json_encode($data),
            UpdateParameter::class,
            'json',
            [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true, ...$this->controllerContext()]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function controllerContext(): array
    {
        $parameter = (new ReflectionMethod(UpdateController::class, 'updateServiceUser'))->getParameters()[1];
        $attribute = $parameter->getAttributes(MapRequestPayload::class)[0]->newInstance();

        return $attribute->serializationContext;
    }
}
