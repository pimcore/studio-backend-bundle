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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final class TaggedIteratorHydrator implements ConfigHydratorLoaderInterface
{
    use MustImplementInterfaceTrait;

    public const string HYDRATOR_TAG = 'pimcore.studio_backend.widget_hydrator';

    public function __construct(
        #[AutowireIterator(self::HYDRATOR_TAG)]
        private readonly iterable $taggedHydratorClasses,
    ) {
    }

    /**
     * @throws MustImplementInterfaceException|NotFoundException
     */
    public function loadHydrator(string $widgetType): WidgetConfigHydratorInterface
    {
        foreach ($this->taggedHydratorClasses as $hydrator) {
            $this->checkInterface($hydrator::class, WidgetConfigHydratorInterface::class);
            if ($widgetType === $hydrator->getSupportedWidgetType()) {
                return $hydrator;
            }
        }

        throw new NotFoundException('Widget Hydrator', $widgetType, 'type');
    }
}
