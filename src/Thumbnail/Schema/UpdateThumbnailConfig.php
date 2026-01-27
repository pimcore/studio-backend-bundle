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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'UpdateThumbnailConfig',
    title: 'Update Thumbnail Config',
    required: ['settings', 'medias', 'mediaOrder'],
    type: 'object'
)]
final readonly class UpdateThumbnailConfig
{
    public function __construct(
        #[Property(
            description: 'Thumbnail settings configuration',
            type: 'object',
            example: [
                'description' => 'My thumbnail description',
                'group' => 'Areas',
                'format' => 'SOURCE',
                'quality' => 95,
                'highResolution' => 0,
                'preserveColor' => false,
                'forceProcessICCProfiles' => false,
                'preserveMetaData' => false,
                'rasterizeSVG' => false,
                'preserveAnimation' => false,
                'downloadable' => false,
            ]
        )]
        private array $settings,
        #[Property(
            description: 'Media configurations with transformation items',
            type: 'object',
            example: [
                'default' => [
                    [
                        'method' => 'scaleByWidth',
                        'arguments' => ['width' => 1140, 'forceResize' => false],
                    ],
                ],
            ]
        )]
        private array $medias,
        #[Property(
            description: 'Media configurations order',
            type: 'object',
            example: ['default' => 0, '(max-width: 940px)' =>1]
        )]
        private array $mediaOrder,
    ) {
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getMedias(): array
    {
        return $this->medias;
    }

    public function getMediaOrder(): array
    {
        return $this->mediaOrder;
    }
}
