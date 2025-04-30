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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Model;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;

final readonly class WidgetElementData
{
    public function __construct(
        private ElementTreeWidgetConfig $widgetConfig,
        private ElementSearchResultItemInterface $resultItem
    ) {
    }

    public function getWidgetConfig(): ElementTreeWidgetConfig
    {
        return $this->widgetConfig;
    }

    public function getResultItem(): ElementSearchResultItemInterface
    {
        return $this->resultItem;
    }
}
