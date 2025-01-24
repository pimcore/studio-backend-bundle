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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ClassDefinitionService implements ClassDefinitionServiceInterface
{
    public function __construct(
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private ClassDefinitionHydratorInterface $classDefinitionHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getClassDefinition(string $dataObjectClass): ClassDefinition
    {
        $cd = $this->classDefinitionHydrator->hydrate(
            $this->classDefinitionRepository->getClassDefinition($dataObjectClass)
        );
        $this->eventDispatcher->dispatch(
            new ClassDefinitionEvent($cd),
            ClassDefinitionEvent::EVENT_NAME
        );

        return $cd;
    }
}
