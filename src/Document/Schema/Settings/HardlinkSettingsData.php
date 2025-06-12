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

/**
 * @internal
 */
#[Schema(
    title: 'Hardlink settings data',
    required: ['sourceId', 'propertiesFromSource', 'childrenFromSource'],
    type: 'object'
)]
final readonly class HardlinkSettingsData implements SettingsDataInterface
{
    public function __construct(
        #[Property(description: 'Source ID', type: 'integer', example: 83)]
        private ?int $sourceId,
        #[Property(description: 'Properties from source', type: 'bool', example: true)]
        private bool $propertiesFromSource,
        #[Property(description: 'Children from source', type: 'bool', example: false)]
        private bool $childrenFromSource,
        #[Property(description: 'Source path', type: 'string', example: '/path/to/source')]
        private ?string $sourcePath = null,
    ) {
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function isPropertiesFromSource(): bool
    {
        return $this->propertiesFromSource;
    }

    public function isChildrenFromSource(): bool
    {
        return $this->childrenFromSource;
    }

    public function getSourcePath(): ?string
    {
        return $this->sourcePath;
    }
}
