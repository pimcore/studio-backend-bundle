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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\Settings;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;

#[Schema(
    title: 'Link Settings Data',
    required: ['internal', 'internalType', 'direct', 'linkType', 'href', 'rawHref'],
    type: 'object'
)]
final readonly class LinkSettingsData implements SettingsDataInterface
{
    public function __construct(
        #[Property(description: 'Internal ID', type: 'integer', example: 83)]
        private ?int $internal,
        #[Property(description: 'Internal type', type: 'string', example: 'asset')]
        private ?string $internalType,
        #[Property(description: 'Direct', type: 'string', example: '/path/to/asset')]
        private string $direct,
        #[Property(description: 'Link type', type: 'string', example: 'direct')]
        private string $linkType,
        #[Property(description: 'Href', type: 'string', example: '/path/to/asset')]
        private string $href,
        #[Property(description: 'Raw Href', type: 'string', example: '/some/raw/href')]
        private string $rawHref,

    ) {
    }

    public function getInternal(): ?int
    {
        return $this->internal;
    }

    public function getInternalType(): ?string
    {
        return $this->internalType;
    }

    public function getDirect(): string
    {
        return $this->direct;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getRawHref(): string
    {
        return $this->rawHref;
    }
}
