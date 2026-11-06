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

namespace Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Event\PreResponse\MaintenanceModeStateEvent;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Hydrator\MaintenanceModeStateHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Schema\MaintenanceModeState;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Pimcore\Tool\MaintenanceModeHelperInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class MaintenanceModeService implements MaintenanceModeServiceInterface
{
    use RequestTrait;

    public function __construct(
        private MaintenanceModeHelperInterface $maintenanceModeHelper,
        private MaintenanceModeStateHydratorInterface $maintenanceModeStateHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack,
    ) {
    }

    public function getState(): MaintenanceModeState
    {
        return $this->hydrateState();
    }

    public function activate(): MaintenanceModeState
    {
        $sessionId = $this->getCurrentSession($this->requestStack)->getId();

        // Without a session id nothing would be exempt and the caller would lock themselves out.
        if ($sessionId === '') {
            throw new EnvironmentException('Maintenance mode requires a session, the current request has none');
        }

        $this->maintenanceModeHelper->activate($sessionId);

        return $this->hydrateState();
    }

    public function deactivate(): MaintenanceModeState
    {
        $this->maintenanceModeHelper->deactivate();

        return $this->hydrateState();
    }

    private function hydrateState(): MaintenanceModeState
    {
        $state = $this->maintenanceModeStateHydrator->hydrate($this->maintenanceModeHelper->isActive());
        $this->eventDispatcher->dispatch(new MaintenanceModeStateEvent($state), MaintenanceModeStateEvent::EVENT_NAME);

        return $state;
    }
}
