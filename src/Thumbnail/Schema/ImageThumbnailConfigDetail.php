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
    schema: 'ImageThumbnailConfigDetail',
    title: 'Image Thumbnail Config Detail',
    required: ['settings', 'writeable', 'medias'],
    type: 'object'
)]
final class ImageThumbnailConfigDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Thumbnail settings', type: ImageThumbnailSettings::class)]
        private readonly ImageThumbnailSettings $settings,
        #[Property(description: 'Is configuration writeable', type: 'boolean', example: true)]
        private readonly bool $writeable,
        #[Property(
            description: 'Media query configurations with transformation items',
            type: 'object',
            example: [
                'default' => [
                    [
                        'method' => 'cover',
                        'arguments' => [
                            'width' => 1920,
                            'height' => 600,
                            'positioning' => 'center',
                            'forceResize' => true,
                        ],
                    ],
                ],
            ]
        )]
        private readonly array $medias,
    ) {
    }

    public function getSettings(): ImageThumbnailSettings
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
