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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'FolderData',
    title: 'Folder Data',
    description: 'Folder Data Scheme for API',
    required: ['folderName'],
    type: 'object'
)]
final readonly class FolderData
{
    public function __construct(
        #[Property(description: 'Folder Name', type: 'string', example: 'Awesome stuff inside')]
        private string $folderName
    ) {
    }

    public function getFolderName(): string
    {
        return $this->folderName;
    }
}
