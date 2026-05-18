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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'ChangeMainDocument',
    title: 'Change Main Document',
    required: ['mainDocumentPath'],
    type: 'object'
)]
final readonly class ChangeMainDocumentParameters
{
    public function __construct(
        #[Property(description: 'Main document path', type: 'string', example: '/path/to/main/document')]
        private ?string $mainDocumentPath,
    ) {
    }

    public function getMainDocumentPath(): ?string
    {
        return $this->mainDocumentPath;
    }
}
