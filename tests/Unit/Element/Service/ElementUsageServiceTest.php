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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\ElementUsageHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementUsageService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ElementUsageServiceTest extends Unit
{
    /**
     * Assets have no "save" workspace permission, so checking it always denied
     * non-admin users and the usage was silently skipped.
     *
     * @throws Exception
     */
    public function testReplaceElementUsageChecksPublishPermissionForAssets(): void
    {
        $user = new User();
        $asset = $this->makeElement(Asset::class, $user, true, $calls, $saved);
        $rewritten = false;

        $service = $this->createService(
            assetServiceResolver: $this->makeEmpty(AssetServiceResolverInterface::class, [
                'rewriteIds' => static function (Asset $element, array $config) use (&$rewritten): Asset {
                    $rewritten = $config === ['object' => [14 => 13]];

                    return $element;
                },
            ]),
        );

        $service->replaceElementUsage($this->makeObject(14), $this->makeObject(13), $asset, $user);

        $this->assertSame([ElementPermissions::PUBLISH_PERMISSION], $calls);
        $this->assertTrue($rewritten);
        $this->assertTrue($saved);
    }

    /**
     * @throws Exception
     */
    public function testReplaceElementUsageChecksSavePermissionForDataObjects(): void
    {
        $user = new User();
        $object = $this->makeElement(Concrete::class, $user, true, $calls, $saved);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'rewriteIds' => static fn (ElementInterface $element) => $element,
            ]),
        );

        $service->replaceElementUsage($this->makeObject(14), $this->makeObject(13), $object, $user);

        $this->assertSame([ElementPermissions::SAVE_PERMISSION], $calls);
        $this->assertTrue($saved);
    }

    /**
     * @throws Exception
     */
    public function testReplaceElementUsageSkipsElementWithoutPermission(): void
    {
        $user = new User();
        $asset = $this->makeElement(Asset::class, $user, false, $calls, $saved);

        $service = $this->createService(
            assetServiceResolver: $this->makeEmpty(AssetServiceResolverInterface::class, [
                'rewriteIds' => static function (): never {
                    self::fail('rewriteIds must not be called without permission');
                },
            ]),
        );

        $service->replaceElementUsage($this->makeObject(14), $this->makeObject(13), $asset, $user);

        $this->assertSame([ElementPermissions::PUBLISH_PERMISSION], $calls);
        $this->assertFalse($saved);
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?AssetServiceResolverInterface $assetServiceResolver = null,
        ?DataObjectServiceResolverInterface $dataObjectServiceResolver = null,
    ): ElementUsageService {
        return new ElementUsageService(
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementUsageHydratorInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(JobExecutionAgentInterface::class),
            $assetServiceResolver ?? $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(DocumentServiceResolverInterface::class),
            $dataObjectServiceResolver ?? $this->makeEmpty(DataObjectServiceResolverInterface::class),
        );
    }

    /**
     * @throws Exception
     */
    private function makeObject(int $id): Concrete
    {
        return $this->makeEmpty(Concrete::class, ['getId' => $id]);
    }

    /**
     * @template T of ElementInterface
     *
     * @param class-string<T> $class
     * @param array<int, string>|null $calls
     *
     * @param-out array<int, string> $calls
     * @param-out bool $saved
     *
     * @return T
     *
     * @throws Exception
     */
    private function makeElement(
        string $class,
        User $expectedUser,
        bool $isAllowed,
        ?array &$calls = null,
        ?bool &$saved = null
    ): ElementInterface {
        $calls = [];
        $saved = false;
        $element = null;

        $element = $this->makeEmpty($class, [
            'getId' => 6,
            'isAllowed' => static function (string $type, ?User $user = null) use (
                &$calls,
                $expectedUser,
                $isAllowed
            ): bool {
                $calls[] = $type;

                return $isAllowed && $user === $expectedUser;
            },
            'save' => static function () use (&$saved, &$element): ElementInterface {
                $saved = true;

                return $element;
            },
        ]);

        return $element;
    }
}
