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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StaticResolverBundle\Lib\VideoResolverInterface;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\AdapterInterface;

/**
 * @internal
 */
final readonly class ThumbnailService implements ThumbnailServiceInterface
{
    public function __construct(
        private AdapterInterface $imageAdapter,
        private VideoResolverInterface $videoResolver
    ) {
    }

    public function isImageAdapterValid(): bool
    {
        return !$this->imageAdapter instanceof GD;
    }

    public function isVideoAdapterValid(): bool
    {
        return $this->videoResolver->isAvailable();
    }
}
