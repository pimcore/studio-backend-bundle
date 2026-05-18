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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\VideoTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video\VideoType;
use Pimcore\Model\DataObject\ClassDefinition\Data\Video;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class VideoService implements VideoServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getVideoTypes(): array
    {
        $types = [];

        foreach ((new Video())->getSupportedTypes() as $supportedType) {
            $type = new VideoType($supportedType);
            $this->eventDispatcher->dispatch(new VideoTypeEvent($type), VideoTypeEvent::EVENT_NAME);
            $types[] = $type;
        }

        return $types;
    }
}
