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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'Assets',
    title: 'Assets',
    required: ['hide_edit_image', 'disable_tree_preview'],
    type: 'object'
)]
final readonly class Assets
{
    public function __construct(
        #[Property(description: 'Hide edit image button', type: 'boolean', example: false)]
        private bool $hide_edit_image,
        #[Property(description: 'Disable tree preview', type: 'boolean', example: true)]
        private bool $disable_tree_preview,
    ) {
    }

    public function getHideEditImage(): bool
    {
        return $this->hide_edit_image;
    }

    public function getDisableTreePreview(): bool
    {
        return $this->disable_tree_preview;
    }
}
