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
