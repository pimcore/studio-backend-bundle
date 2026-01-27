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
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\Config;

#[Schema(
    schema: 'ThumbnailConfigurationData',
    title: 'Thumbnail Configuration Data',
    required: ['writeable'],
    type: 'object'
)]
final class ThumbnailConfig extends Config
{
    public function __construct(
        string $id,
        string $name,
        ElementIcon $icon,
        #[Property(description: 'Is configuration writeable', type: 'bool', example: true)]
        private readonly bool $writeable,
    ) {
        parent::__construct($id, $name, $icon);
    }

    public function isWriteable(): bool
    {
        return $this->writeable;
    }
}
