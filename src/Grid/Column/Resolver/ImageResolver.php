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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver;

use Carbon\Carbon;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class ImageResolver implements ColumnResolverInterface
{
    public function resolve(Column $columnDefinition, ElementInterface $element): mixed
    {
        /** @var Asset $element */
        return $element->getRealFullPath();
    }

    public function getType(): string
    {
        return 'image';
    }
}