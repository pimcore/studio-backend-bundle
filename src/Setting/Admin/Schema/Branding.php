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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;

#[Schema(
    schema: 'Branding',
    title: 'Branding',
    required: [
        'backgroundShade',
        'brandColor',
        'loginScreenCustomBackgroundImage',
        'loginScreenCustomImage',
    ],
    type: 'object'
)]
final readonly class Branding
{
    public function __construct(
        #[Property(description: 'Background shade', type: 'string', example: '#CCCCCC')]
        private string $backgroundShade,
        #[Property(description: 'Brand color', type: 'string', example: '#FFCC00')]
        private string $brandColor,
        #[Property(
            ref: RelatedElementData::class,
            description: 'Custom image for login screen',
            type: 'object'
        )]
        private ?RelatedElementData $loginScreenCustomBackgroundImage = null,
        #[Property(
            ref: RelatedElementData::class,
            description: 'Custom image for login screen',
            type: 'object'
        )]
        private ?RelatedElementData $loginScreenCustomImage = null,
    ) {

    }

    public function getLoginScreenCustomBackgroundImage(): ?RelatedElementData
    {
        return $this->loginScreenCustomBackgroundImage;
    }

    public function getLoginScreenCustomImage(): ?RelatedElementData
    {
        return $this->loginScreenCustomImage;
    }

    public function getBackGroundShade(): string
    {
        return $this->backgroundShade;
    }

    public function getBrandColor(): string
    {
        return $this->brandColor;
    }
}
