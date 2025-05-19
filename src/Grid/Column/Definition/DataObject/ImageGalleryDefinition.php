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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject;

use Override;

/**
 * @internal
 */
final readonly class ImageGalleryDefinition extends AbstractDefinition
{
    public function getType(): string
    {
        return 'data-object.imageGallery';
    }

    public function getFrontendType(): string
    {
        return 'imageGallery';
    }

    #[Override]
    public function isSortable(): bool
    {
        return false;
    }

    #[Override]
    public function isFilterable(): bool
    {
        return false;
    }
}
