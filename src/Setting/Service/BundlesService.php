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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StudioBackendBundle\Setting\Event\PreResponse\ActiveBundleEvent;
use Pimcore\Bundle\StudioBackendBundle\Setting\Hydrator\ActiveBundleHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class BundlesService implements BundlesServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ActiveBundleHydratorInterface $hydrator,
        private PimcoreBundleManager $bundleManager,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveBundles(): array
    {
        $bundles = $this->bundleManager->getActiveBundles();
        $activeBundles = [];
        foreach ($bundles as $bundle) {
            $activeBundle = $this->hydrator->hydrate($bundle);
            $this->eventDispatcher->dispatch(
                new ActiveBundleEvent($activeBundle),
                ActiveBundleEvent::EVENT_NAME
            );
            $activeBundles[] = $activeBundle;
        }

        return $activeBundles;
    }
}
