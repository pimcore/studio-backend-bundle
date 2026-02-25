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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'JsonExport',
    title: 'JSON Export',
    required: ['json', 'fileName'],
    type: 'object'
)]
final readonly class JsonExport
{
    public function __construct(
        #[Property(description: 'JSON encoded export data', type: 'string')]
        private string $json,
        #[Property(description: 'Suggested file name for download', type: 'string', example: 'export.json')]
        private string $fileName,
    ) {
    }

    public function getJson(): string
    {
        return $this->json;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
}
