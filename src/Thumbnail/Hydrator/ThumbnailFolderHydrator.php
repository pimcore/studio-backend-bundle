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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailFolder;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;

/**
 * @internal
 */
final readonly class ThumbnailFolderHydrator implements ThumbnailFolderHydratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(string $name, array $children): ThumbnailFolder
    {
        return new ThumbnailFolder(
            'group_' . $name,
            $name,
            new ElementIcon(ElementIconTypes::NAME->value, 'folder'),
            $children
        );
    }
}
