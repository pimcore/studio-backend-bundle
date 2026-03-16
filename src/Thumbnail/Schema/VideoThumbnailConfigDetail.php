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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'VideoThumbnailConfigDetail',
    title: 'Video Thumbnail Config Detail',
    required: ['settings', 'writeable', 'medias'],
    type: 'object'
)]
final class VideoThumbnailConfigDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Thumbnail settings', type: VideoThumbnailSettings::class)]
        private readonly VideoThumbnailSettings $settings,
        #[Property(description: 'Is configuration writeable', type: 'boolean', example: true)]
        private readonly bool $writeable,
        #[Property(
            description: 'Media query configurations with transformation items',
            type: 'object',
            example: [
                'default' => [
                    [
                        'method' => 'scaleByHeight',
                        'arguments' => [
                            'height' => 300,
                        ],
                    ],
                ],
                '500K' => [
                    [
                        'method' => 'scaleByHeight',
                        'arguments' => [
                            'height' => 500,
                        ],
                    ],
                ],
            ]
        )]
        private readonly array $medias,
    ) {
    }

    public function getSettings(): VideoThumbnailSettings
    {
        return $this->settings;
    }

    public function isWriteable(): bool
    {
        return $this->writeable;
    }

    public function getMedias(): array
    {
        return $this->medias;
    }
}
