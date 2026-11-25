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
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ElementUsageServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testReplaceElementUsageRewritesWhenSourceAndTargetAreViewable(): void
    {
        $user = new User();
        $dependent = $this->makeDependent($saved);
        $rewritten = false;

        $service = $this->createService(static function (Concrete $element, array $config) use (&$rewritten): Concrete {
            $rewritten = $config === ['object' => [14 => 13]];

            return $element;
        });

        $source = $this->makeObject(14, true, $sourceChecks);
        $target = $this->makeObject(13, true, $targetChecks);
        $service->replaceElementUsage($source, $target, $dependent, $user);

        $this->assertTrue($rewritten);
        $this->assertTrue($saved);
        // the view permission is checked for the user the replacement runs for
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, $user]], $sourceChecks);
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, $user]], $targetChecks);
    }

    /**
     * GHSA-pg5f-74vr-w237: the rewrite must not happen without view access on the source.
     *
     * @throws Exception
     */
    public function testReplaceElementUsageSkipsRewriteWhenSourceIsNotViewable(): void
    {
        $dependent = $this->makeDependent($saved);
        $service = $this->createService(static function (): never {
            self::fail('rewriteIds must not be called without view access on the source');
        });

        $service->replaceElementUsage(
            $this->makeObject(14, false),
            $this->makeObject(13, true),
            $dependent,
            new User()
        );

        $this->assertFalse($saved);
    }

    /**
     * @throws Exception
     */
    public function testReplaceElementUsageSkipsRewriteWhenTargetIsNotViewable(): void
    {
        $dependent = $this->makeDependent($saved);
        $service = $this->createService(static function (): never {
            self::fail('rewriteIds must not be called without view access on the target');
        });

        $service->replaceElementUsage(
            $this->makeObject(14, true),
            $this->makeObject(13, false),
            $dependent,
            new User()
        );

        $this->assertFalse($saved);
    }

    /**
     * @throws Exception
     */
    private function createService(callable $rewriteIds): ElementUsageService
    {
        return new ElementUsageService(
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementUsageHydratorInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(JobExecutionAgentInterface::class),
            $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(DocumentServiceResolverInterface::class),
            $this->makeEmpty(DataObjectServiceResolverInterface::class, ['rewriteIds' => $rewriteIds]),
        );
    }

    /**
     * @param array<int, array{string, User|null}>|null $checks
     *
     * @param-out array<int, array{string, User|null}> $checks
     *
     * @throws Exception
     */
    private function makeObject(int $id, bool $viewAllowed, ?array &$checks = null): Concrete
    {
        $checks = [];

        return $this->makeEmpty(Concrete::class, [
            'getId' => $id,
            'isAllowed' => static function (string $type, ?User $user = null) use (&$checks, $viewAllowed): bool {
                $checks[] = [$type, $user];

                return $viewAllowed;
            },
        ]);
    }

    /**
     * The element whose relations are rewritten; the user may save it.
     *
     * @param-out bool $saved
     *
     * @throws Exception
     */
    private function makeDependent(?bool &$saved = null): Concrete
    {
        $saved = false;
        $element = null;

        $element = $this->makeEmpty(Concrete::class, [
            'getId' => 6,
            'isAllowed' => true,
            'save' => static function () use (&$saved, &$element): Concrete {
                $saved = true;

                return $element;
            },
        ]);

        return $element;
    }
}
