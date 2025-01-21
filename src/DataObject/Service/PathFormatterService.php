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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\Helper\PathFormatterResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\FormatedPathEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\FormatedPathHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Legacy\PathFormatterHelperInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\PathFormatterParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\PathFormatterAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\PathFormatterInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class PathFormatterService implements PathFormatterServiceInterface
{
    public function __construct(
        private ConcreteObjectResolverInterface $concreteObjectResolver,
        private PathFormatterHelperInterface $pathFormatterHelper,
        private FormatedPathHydratorInterface $formatedPathHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private PathFormatterResolverInterface $pathFormatterResolver
    ) {
    }

    public function formatPath(PathFormatterParameter $parameter): array
    {
        $concreteObject = $this->concreteObjectResolver->getById($parameter->getObjectId());

        if (!$concreteObject) {
            throw new NotFoundException('object', $parameter->getObjectId());
        }

        try {
            $result = [];
            $formatedPaths = $this->convert($concreteObject, $parameter->getContext(), $parameter->getTargets());

            foreach ($formatedPaths as $key => $formatedPath) {
                $formatedPath = $this->formatedPathHydrator->hydrate($key, $formatedPath);

                $this->eventDispatcher->dispatch(
                    new FormatedPathEvent($formatedPath),
                    FormatedPathEvent::EVENT_NAME
                );

                $result[] = $formatedPath;
            }

            return $result;

        } catch (InvalidArgumentException) {
            return [];
        }
    }

    protected function convert(Concrete $source, array $context, array $targets): array
    {
        $fd = $this->pathFormatterHelper->getPathFormatterFieldDefinition($source, $context);

        if (!$fd instanceof PathFormatterAwareInterface) {
            throw new InvalidArgumentException('FieldDefinition is not PathFormatterAware');
        }

        $formatterClass = $fd->getPathFormatterClass();

        if (!$formatterClass) {
            throw new InvalidArgumentException('PathFormatterClass is not defined');
        }

        $pathFormatter = $this->pathFormatterResolver->resolvePathFormatter($formatterClass);

        if (!$pathFormatter instanceof PathFormatterInterface) {
            throw new InvalidArgumentException('PathFormatter is not an instance of PathFormatterInterface');
        }

        return $pathFormatter->formatPath([], $source, $targets, [
            'fd' => $fd,
            'context' => $context,
        ]);
    }
}
