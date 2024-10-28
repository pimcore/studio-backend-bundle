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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\LayoutEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class LayoutService implements LayoutServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private DataObjectServiceInterface $dataObjectService,
        private EventDispatcherInterface $eventDispatcher,
        private IconServiceInterface $iconService,
        private SecurityServiceInterface $securityService,
    ) {
    }


    /**
     * @throws AccessDeniedException|InvalidElementTypeException|NotFoundException|UserNotFoundException
     */
    public function getDataObjectLayout(int $id): Layout
    {
        $user = $this->securityService->getCurrentUser();
        $dataObject = $this->dataObjectService->getDataObjectElement(
            $user,
            $id
        );

        $dataObject = $this->getLatestVersionForUser($dataObject, $user);
        if (!$dataObject instanceof Concrete) {
            throw new InvalidElementTypeException(
                sprintf('DataObject class (%s) is not a concrete object', get_class($dataObject))
            );
        }

        try {
            $layout = $dataObject->getClass()->getLayoutDefinitions();
        } catch (Exception) {
            throw new NotFoundException(type: 'class for data object', id: $id);
        }

        if (!$layout instanceof Panel) {
            throw new NotFoundException(type: 'class layout for data object', id: $id);
        }

        //ToDo: Consider custom layouts once implemented
        $hydratedLayout = $this->hydrateLayout($layout);
        $this->eventDispatcher->dispatch(new LayoutEvent($hydratedLayout), LayoutEvent::EVENT_NAME);

        return $hydratedLayout;
    }

    private function hydrateLayout(
        Panel $panel
    ): Layout
    {
        return new Layout(
            $panel->getName(),
            $panel->getDatatype(),
            $panel->fieldtype,
            $panel->getType(),
            $panel->getLayout(),
            $panel->getRegion(),
            $panel->getTitle(),
            $panel->getWidth(),
            $panel->getHeight(),
            $panel->getCollapsible(),
            $panel->getCollapsed(),
            $panel->getBodyStyle(),
            $panel->getLocked(),
            $panel->getChildren(),
            $this->iconService->getIconForLayout($panel->getIcon()),
            $panel->getLabelAlign(),
            $panel->getLabelWidth(),
            $panel->getBorder()
        );
    }
}
