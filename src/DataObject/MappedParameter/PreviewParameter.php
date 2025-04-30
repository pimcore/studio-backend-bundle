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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Data Object Preview Parameters',
    required: ['id'],
    type: 'object'
)]
final readonly class PreviewParameter
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 83)]
        private int $id,
        #[Property(description: 'Site', type: 'integer', default: 0, example: 1)]
        private int $site = 0
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): int
    {
        return $this->site;
    }
}
