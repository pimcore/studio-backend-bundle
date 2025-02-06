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

use Pimcore\Bundle\StudioBackendBundle\Perspective\Event\WidgetTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class WidgetService implements WidgetServiceInterface
{
    public function __construct(
        private WidgetTypeHydratorInterface $widgetTypeHydrator,
        private EventDispatcherInterface $eventDispatcher,
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

    public function getWidgetTypes(): array
    {
        return $this->widgetTypes;
    }
}
