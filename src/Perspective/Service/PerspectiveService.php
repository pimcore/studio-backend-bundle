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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Event\PerspectiveConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\PerspectiveConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\PerspectiveConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateConfigurationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class PerspectiveService implements PerspectiveServiceInterface
{
    use ValidateConfigurationTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private PerspectiveConfigHydratorInterface $configHydrator,
        private PerspectiveConfigRepositoryInterface $configRepository
    ) {
    }

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function getConfigData(string $perspectiveId): PerspectiveConfig
    {
        $configData = $this->configRepository->getConfiguration($perspectiveId);

        return $this->hydrateAndDispatch($configData);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function hydrateAndDispatch(array $configData): PerspectiveConfig
    {
        $perspective = $this->configHydrator->hydrate($configData);
        $this->eventDispatcher->dispatch(
            new PerspectiveConfigEvent($perspective),
            PerspectiveConfigEvent::EVENT_NAME
        );

        return $perspective;
    }
}
