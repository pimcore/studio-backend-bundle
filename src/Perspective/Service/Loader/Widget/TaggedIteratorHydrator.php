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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorHydrator implements ConfigHydratorLoaderInterface
{
    use MustImplementInterfaceTrait;

    public const string HYDRATOR_TAG = 'pimcore.studio_backend.widget_hydrator';

    public function __construct(
        #[TaggedIterator(self::HYDRATOR_TAG)]
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
