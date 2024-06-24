<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;

/**
 * @internal
 */
interface TopicLoaderInterface
{
    /**
     * @return TopicCollection
     */
    public function loadTopics(): TopicCollection;
}