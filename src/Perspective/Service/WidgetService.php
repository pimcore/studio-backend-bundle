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
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Event\WidgetConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Event\WidgetTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetConfigInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetType;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\ConfigHydratorLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\ConfigRepositoryLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class WidgetService implements WidgetServiceInterface
{
    public function __construct(
        private ConfigHydratorLoaderInterface $configHydratorLoader,
        private ConfigRepositoryLoaderInterface $configRepositoryLoader,
        private EventDispatcherInterface $eventDispatcher,
        private WidgetTypeHydratorInterface $widgetTypeHydrator,
        private array $widgetTypes
    ) {
    }

    /**
     * @return WidgetType[]
     */
    public function listWidgetTypes(): array
    {
        $widgetTypes = [];
        foreach ($this->getWidgetTypes() as $type) {
            $hydratedType = $this->widgetTypeHydrator->hydrate($type);
            $this->eventDispatcher->dispatch(new WidgetTypeEvent($hydratedType), WidgetTypeEvent::EVENT_NAME);
            $widgetTypes[] = $hydratedType;
        }

        return $widgetTypes;
    }

    /**
     * @throws InvalidArgumentException|NotFoundException
     */
    public function getWidgetConfigData(string $widgetType, string $widgetId): WidgetConfigInterface
    {
        $this->validateWidgetType($widgetType);
        try {
            $data = $this->configRepositoryLoader->loadRepository($widgetType)->getConfigData($widgetId);
            $hydrator = $this->configHydratorLoader->loadHydrator($widgetType);
        } catch (MustImplementInterfaceException $exception) {
            throw new InvalidArgumentException(
                sprintf('Invalid widget implementation: %s', $exception->getMessage()),
                $exception
            );
        }

        $hydrated = $hydrator->hydrate($data);

        $this->eventDispatcher->dispatch(new WidgetConfigEvent($hydrated), WidgetConfigEvent::EVENT_NAME);

        return $hydrated;
    }

    public function getWidgetTypes(): array
    {
        return $this->widgetTypes;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateWidgetType(string $widgetType): void
    {
        if (!in_array($widgetType, $this->getWidgetTypes(), true)) {
            throw new InvalidArgumentException(sprintf('Invalid widget type: %s', $widgetType));
        }
    }
}
